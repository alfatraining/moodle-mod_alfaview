# alfaview Moodle plugin

alfaview is an app based video conferencing system (https://alfaview.com).
This plugin integrates the alfaview video conferencing capabilities with Moodle: teachers add an
"alfaview classroom" activity to a course and link it to a room of their alfaview account, and
participants join the room with one click.

## Requirements

- Moodle 4.5.8 or later
- PHP 8.3 or later
- An alfaview account with API credentials. The user who created the API key needs permission to
  administrate users and rooms.
- The alfaview app on the participants' devices ([download](https://alfaview.com/download)), or a supported browser
  for joining with the alfaview web client

## Installing the plugin

Download the latest release of this plugin and install via the plugin installer (requires administrative privileges).

## Configuration

1. Get API credentials in the alfaview admin app, under [Account Management](https://app.alfaview.com/#/settings/api-keys).
   Alternatively you can contact support@alfaview.com to obtain API credentials for your alfaview account.
2. In Moodle, go to Site administration → Plugins → Activity modules → alfaview and enter the Alias (Client ID),
   Secret and Account ID. After saving, the page shows whether the connection works.

## Usage

- Add an "alfaview classroom" activity to a course, give it a name and an optional description, and select
  a room. Rooms are created in your [alfaview account](https://app.alfaview.com/).
- Participants click "Join classroom" to open the room in the alfaview app, or "Join in browser" to use the
  alfaview web client in a new tab. Rooms that block joining in the browser show an error message instead.
- Users with the capability `mod/alfaview:moderate` join as moderators, everyone else as participants.
  By default, teachers, non-editing teachers, course creators and managers are moderators.
- The activity supports completion on view.
- The plugin creates two group links per room (moderator and participant) and deletes them again when the last
  activity using the room is deleted.
- Activities are included in course backups, copies and imports. Restored activities keep their room. When a course
  is restored on another Moodle site whose alfaview account doesn't have the room, a teacher has to select a room in
  the activity settings.

## Privacy

When a user joins a classroom, their first and last name are sent to alfaview as the display name.
The plugin doesn't store personal data in Moodle.
