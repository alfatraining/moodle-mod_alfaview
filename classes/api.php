<?php
// This file is part of the alfaview plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles API calls to the alfaview API.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Alfaview\Alfaview;
use Alfaview\Model\AuthenticationAuthorizationCodeCredentials;
use Alfaview\Model\AuthenticationGuestAccessCredentials;
use Alfaview\Model\CommonPermissions;
use Alfaview\Model\CommonRoom;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/alfaview/vendor/autoload.php');

class mod_alfaview_api
{
    private $av;
    private $apiHost;
    private $apiClientId;
    private $apiCode;
    private $apiCompanyId;
    private $apiGuestCode;
    private $accessToken;

    public function __construct()
    {
        $config = get_config('mod_alfaview');

        $this->apiHost = $config->api_host;
        $this->apiClientId = $config->api_client_id;
        $this->apiCode = $config->api_code;
        $this->apiCompanyId = $config->api_company_id;
        $this->apiGuestCode = $config->api_guest_code;

        $this->av = new Alfaview();
        $this->av->setHost($this->apiHost);
    }

    public function authenticate()
    {
        if (!empty($this->accessToken) && $this->av->isAuthenticated($this->accessToken)) {
            return;
        }

        $credentials = new AuthenticationAuthorizationCodeCredentials();
        $credentials->setClientId($this->apiClientId);
        $credentials->setCode($this->apiCode);
        $credentials->setCompanyId($this->apiCompanyId);

        $response = $this->av->authenticate($credentials);
        $this->accessToken = $response->reply->getAccessToken();
    }

    public function createTeacher($roomId)
    {
        $this->authenticate();
        return $this->createUser($roomId, true, true);
    }

    public function createStudent($roomId)
    {
        $this->authenticate();
        return $this->createUser($roomId);
    }

    public function createUser($roomId, $vip = false, $promote = false)
    {
        // create a reusable guest object
        $guestAccessCredentials = new AuthenticationGuestAccessCredentials();
        $guestAccessCredentials->setDisplayName("Guest created by Moodle");
        $guestAccessCredentials->setShareable(true);
        $guestAccessCredentials->setExpiry("-1"); // never expire
        $guestAccessCredentials->setRoomId($roomId);
        $guestAccessCredentials->setCompanyId($this->apiCompanyId);
        $guestAccessCredentials->setCode($this->apiGuestCode);

        $response = $this->av->createAuthentication($this->accessToken, $guestAccessCredentials);
        $userId = $response->reply->getUserId();

        // assign permissions to guest object in the selected room
        $guestPermissions = new CommonPermissions();
        $guestPermissions->setVoice(true);
        $guestPermissions->setVideo(true);
        $guestPermissions->setJoin(true);
        $guestPermissions->setChat(true);
        $guestPermissions->setScreen(true);
        $guestPermissions->setFairUse(true);
        $guestPermissions->setVip($vip);
        $guestPermissions->setPromote($promote);

        $room = new CommonRoom();
        $room->setPermissions(array($userId => $guestPermissions));

        $response = $this->av->updateRoom($this->accessToken, $roomId, $room);
        return $userId;
    }

    public function createJoinLink($userId, $displayName, $roomId)
    {
        // contact the alfaview api as guest user
        $credentials = new AuthenticationGuestAccessCredentials();
        $credentials->setUserId($userId);
        $credentials->setCode($this->apiGuestCode);
        $credentials->setCompanyId($this->apiCompanyId);
        $credentials->setDisplayName($displayName);
        $credentials->setRoomId($roomId);

        $response = $this->av->authenticate($credentials);
        $accessToken = $response->reply->getAccessToken();

        // create guest link
        $response = $this->av->createJoinLink($accessToken, $roomId);
        $joinLink = $response->reply->getJoinLink();

        return $joinLink;
    }

    public function listRooms()
    {
        $this->authenticate();
        $response = $this->av->roomList($this->accessToken);
        $rooms = $response->reply->getRooms();

        return $rooms;
    }
}
