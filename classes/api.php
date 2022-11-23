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
use Alfaview\Model\CommonRoomType;
use Alfaview\Model\GuestServiceV2GroupLinkCreation;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/vendor/autoload.php');

class mod_alfaview_api
{
    private $av;
    private $apiClientId;
    private $apiCode;
    private $apiCompanyId;
    private $apiGuestCode;
    private $accessToken;

    public function __construct()
    {
        $config = get_config('mod_alfaview');

        $this->apiClientId = $config->api_client_id;
        $this->apiCode = $config->api_code;
        $this->apiCompanyId = $config->api_company_id;
        $this->apiGuestCode = $config->api_guest_code;

        $this->av = new Alfaview();
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
        $groupLinkRole = $vip && $promote ? 'Moderator' : 'Participant';

        $permissionGroupId = $this->av->getPermissionGroupId($this->accessToken, $groupLinkRole);
        $groupLink = new GuestServiceV2GroupLinkCreation();
        $groupLink->setPermissionGroupId($permissionGroupId);
        $groupLink->setDescription("Guest created by Moodle");
        $response = $this->av->createGroupLink($this->accessToken, $roomId, [$groupLink]);

        $groupLink = $response->reply->getGroupLinks()[0];
        $groupLinkId = $groupLink->getAccessKey();

        return $groupLinkId;
    }

    public function createJoinLink($userId, $displayName, $roomId)
    {
        // contact the alfaview api as guest user
        $response = $this->av->guestAuthenticate(
            $this->apiCompanyId,
            $roomId,
            $userId,
            $displayName);
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
        $rooms = array_filter($rooms, function($room){
            return $room->getType() === CommonRoomType::ROOM;
        });

        return $rooms;
    }
}
