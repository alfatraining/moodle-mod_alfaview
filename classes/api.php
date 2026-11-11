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
use Alfaview\Client\Model\RoomFeatures;

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
            // The SDK sends this token with every following request.
            $this->av->setAccessToken($this->accesstoken);
        } catch (ApiException $e) {
            throw new moodle_exception('connection_status_error', 'mod_alfaview');
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

            $permissiongroups = $this->av->permissionsApi->listPermissionGroups();

            $grouplinkcreate = new GroupLinkCreate();
            $grouplinkcreate->setDescription("$grouplinkrole created by Moodle for $roomid");
            $grouplinkcreate->setDialInAllowed(false);

            foreach ($permissiongroups as $group) {
                if ($group->getName() === $grouplinkrole) {
                    $grouplinkcreate->setPermissionGroupId($group->getId());
                }
            }

            $grouplinks = new CreateGroupLinksRequestBody();
            $grouplinks->setCreate([$grouplinkcreate]);
            $response = $this->av->guestsApi->createGroupLink($roomid, $grouplinks);

            $grouplinkid = $response[0]->getId();
            $grouplink = $this->av->guestsApi->getGroupLink($grouplinkid);

            return $grouplink->getAccessKey();
        } catch (ApiException $e) {
            throw new moodle_exception('user_create_error', 'mod_alfaview');
        }
    }

    /**
     * Deletes the group links of a room that have one of the given access keys.
     *
     * The plugin only stores the access keys, so the group link IDs are looked up first.
     *
     * @param string $roomid The room ID.
     * @param string[] $accesskeys The access keys of the group links to delete.
     * @return bool True if all group links are gone, false if only some of them could be deleted.
     * @throws moodle_exception If none of the group links could be deleted.
     */
    public function delete_group_links($roomid, array $accesskeys) {
        $grouplinkids = [];
        try {
            $this->authenticate();
            $pagetoken = null;
            do {
                $reply = $this->av->guestsApi->listGroupLinksForRoom($roomid, $pagetoken);
                foreach ($reply->getData() as $grouplink) {
                    if (in_array($grouplink->getAccessKey(), $accesskeys, true)) {
                        $grouplinkids[] = $grouplink->getId();
                    }
                }
                $pagetoken = $reply->getNextPageToken();
            } while (!empty($pagetoken));
        } catch (ApiException $e) {
            // The room no longer exists in alfaview, so neither do its group links.
            if ($e->getCode() == 404) {
                return true;
            }
            throw new moodle_exception('group_link_delete_error', 'mod_alfaview');
        }

        $failed = 0;
        foreach ($grouplinkids as $grouplinkid) {
            try {
                $this->av->guestsApi->deleteGroupLink($grouplinkid, $roomid);
            } catch (ApiException $e) {
                if ($e->getCode() != 404) {
                    $failed++;
                }
            }
        }

        if ($failed > 0 && $failed == count($grouplinkids)) {
            throw new moodle_exception('group_link_delete_error', 'mod_alfaview');
        }

        return $failed == 0;
    }

    /**
     * Creates a join link for a user.
     *
     * @param string $userid The user ID.
     * @param string $displayname The display name.
     * @param string $roomid The room ID.
     * @param bool $webclient Whether to return the link for the web client instead of the app.
     * @return string The join link.
     */
    public function create_join_link($userid, $displayname, $roomid, $webclient = false) {
        // Contact the alfaview api as guest user.
        $guestcredentials = new AuthenticateGroupLinkRequestBody();
        $guestcredentials->setCompanyId($this->apicompanyid);
        $guestcredentials->setRoomId($roomid);
        $guestcredentials->setAccessKey($userid);
        $guestcredentials->setDisplayName($displayname);

        try {
            $response = $this->av->authenticationApi->authenticateGroupLink($guestcredentials);
            $joinlink = $webclient ? $response->getWebclientLaunchUrl() : $response->getClientLaunchUrl();
        } catch (ApiException $e) {
            throw new moodle_exception('join_link_create_error', 'mod_alfaview');
        }

        // The API returns no web client link if the group link is not (or no longer) valid.
        if (empty($joinlink)) {
            throw new moodle_exception('join_link_create_error', 'mod_alfaview');
        }

        return $joinlink;
    }

    /**
     * Checks whether a room blocks joining with the web client.
     *
     * Rooms set to "default" follow the account setting, which the API doesn't expose, so only an explicit block counts.
     *
     * @param string $roomid The room ID.
     * @return bool True if the web client is blocked for the room.
     */
    public function is_webclient_blocked($roomid) {
        try {
            $this->authenticate();
            $features = $this->av->roomsApi->getRoom($roomid)->getFeatures();
        } catch (ApiException $e) {
            throw new moodle_exception('room_get_error', 'mod_alfaview');
        }

        return $features !== null && $features->getBlockWebclient() === RoomFeatures::BLOCK_WEBCLIENT_ENABLED;
    }

    /**
     * Lists all available rooms.
     *
     * @return array Array of room objects.
     */
    public function list_rooms() {
        try {
            $this->authenticate();
            $roomlistreply = $this->av->roomsApi->listRooms([Room::TYPE_ROOM]);
            $nextpagetoken = $roomlistreply->getNextPageToken();
            $rooms = $roomlistreply->getData();

            while ($nextpagetoken != null) {
                $roomlistreply = $this->av->roomsApi->listRooms([Room::TYPE_ROOM], $nextpagetoken);

                $rooms = array_merge($rooms, $roomlistreply->getData());
                $nextpagetoken = $roomlistreply->getNextPageToken();
            }

            return $rooms;
        } catch (ApiException $e) {
            throw new moodle_exception('room_list_error', 'mod_alfaview');
        }

        return [];
    }
}
