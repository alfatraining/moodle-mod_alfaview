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

use Alfaview\Client\Alfaview;
use Alfaview\Client\ApiException;
use Alfaview\Client\Model\APICredentials;
use Alfaview\Client\Model\AuthenticateGroupLinkRequestBody;
use Alfaview\Client\Model\CreateGroupLinksRequestBody;
use Alfaview\Client\Model\GroupLinkCreate;
use Alfaview\Client\Model\Room;
use Alfaview\Model\AuthenticationAuthorizationCodeCredentials;
use Alfaview\Model\CommonRoomType;
use Alfaview\Model\GuestServiceV2GroupLinkCreation;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/vendor/autoload.php');

/**
 * API wrapper class for alfaview video conferencing.
 *
 * Handles authentication and API calls to the alfaview platform including
 * room management, user creation, and join link generation.
 */
class mod_alfaview_api {
    /** @var Alfaview The alfaview API instance. */
    private $av;
    /** @var string The API client ID. */
    private $apiclientid;
    /** @var string The API authorization code. */
    private $apicode;
    /** @var string The API company ID. */
    private $apicompanyid;
    /** @var string The current access token. */
    private $accesstoken;

    /**
     * Constructor - initializes API configuration.
     */
    public function __construct() {
        $config = get_config('mod_alfaview');

        $this->apiclientid = $config->api_client_id;
        $this->apicode = $config->api_code;
        $this->apicompanyid = $config->api_company_id;

        $this->av = new Alfaview();
    }

    /**
     * Authenticates with the alfaview API.
     *
     * Obtains and stores an access token if not already authenticated.
     */
    public function authenticate() {
        if (!empty($this->accesstoken)) {
            return;
        }

        $credentials = new APICredentials();
        $credentials->setClientId($this->apiclientid);
        $credentials->setKey($this->apicode);
        $credentials->setCompanyId($this->apicompanyid);

        try {
            $response = $this->av->authenticationApi->authenticateAPIKey($credentials);
            $this->accesstoken = $response->getAccessToken();
        } catch (ApiException $e) {
            throw new moodle_exception(
                get_string('connection_status_error', 'mod_alfaview'),
                'mod_alfaview'
            );
        }
    }

    /**
     * Creates a teacher user for a room.
     *
     * @param string $roomid The room ID.
     * @return string The user access key.
     */
    public function create_teacher($roomid) {
        return $this->create_user($roomid, true, true);
    }

    /**
     * Creates a student user for a room.
     *
     * @param string $roomid The room ID.
     * @return string The user access key.
     */
    public function create_student($roomid) {
        return $this->create_user($roomid);
    }

    /**
     * Creates a user for a room.
     *
     * @param string $roomid The room ID.
     * @param bool $vip Whether user is VIP.
     * @param bool $promote Whether to promote user.
     * @return string The user access key.
     */
    public function create_user($roomid, $vip = false, $promote = false) {
        try {
            $this->authenticate();

            $grouplinkrole = $vip && $promote ? 'Moderator' : 'Participant';

            $permissiongroups = $this->av->roomsApi->listPermissionGroups($this->accesstoken);

            $grouplinkcreate = new GroupLinkCreate();
            $grouplinkcreate->setDescription("$grouplinkrole created by alfatraining-com for $roomid");
            $grouplinkcreate->setDialInAllowed(false);

            foreach ($permissiongroups as $group) {
                if ($group->getName() === $grouplinkrole) {
                    $grouplinkcreate->setPermissionGroupId($group->getId());
                }
            }

            $grouplinks = new CreateGroupLinksRequestBody();
            $grouplinks->setCreate([$grouplinkcreate]);
            $response = $this->av->guestsApi->createGroupLink($roomid, $grouplinks, $this->accesstoken);

            $grouplinkid = $response[0]->getId();
            $grouplink = $this->av->guestsApi->getGroupLink($grouplinkid, $this->accesstoken);

            return $grouplink->getAccessKey();
        } catch (ApiException $e) {
            throw new moodle_exception(
                get_string('user_create_error', 'mod_alfaview'),
                'mod_alfaview'
            );
        }
    }

    /**
     * Creates a join link for a user.
     *
     * @param string $userid The user ID.
     * @param string $displayname The display name.
     * @param string $roomid The room ID.
     * @return string The join link.
     */
    public function create_join_link($userid, $displayname, $roomid) {
        // Contact the alfaview api as guest user.
        $guestcredentials = new AuthenticateGroupLinkRequestBody();
        $guestcredentials->setCompanyId($this->apicompanyid);
        $guestcredentials->setRoomId($roomid);
        $guestcredentials->setAccessKey($userid);
        $guestcredentials->setDisplayName($displayname);

        try {
            $response = $this->av->authenticationApi->authenticateGroupLink($guestcredentials);

            return $response->getClientLaunchUrl();
        } catch (ApiException $e) {
            throw new moodle_exception(
                get_string('join_link_create_error', 'mod_alfaview'),
                'mod_alfaview'
            );
        }
    }

    /**
     * Lists all available rooms.
     *
     * @return array Array of room objects.
     */
    public function list_rooms() {
        try {
            $this->authenticate();
            $roomlistreply = $this->av->roomsApi->listRooms($this->accesstoken, Room::TYPE_ROOM);
            $nextpagetoken = $roomlistreply->getNextPageToken();
            $rooms = $roomlistreply->getData();

            while ($nextpagetoken != null) {
                $roomlistreply = $this->av->roomsApi->listRooms(
                    $this->accesstoken,
                    Room::TYPE_ROOM,
                    $nextpagetoken
                );

                $rooms = array_merge($rooms, $roomlistreply->getData());
                $nextpagetoken = $roomlistreply->getNextPageToken();
            }

            return $rooms;
        } catch (ApiException $e) {
            throw new moodle_exception(
                get_string('room_list_error', 'mod_alfaview'),
                'mod_alfaview'
            );
        }

        return [];
    }
}
