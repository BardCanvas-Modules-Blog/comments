
# Comments Module Change Log

## [1.12.8] - 2024-08-20

- Added offending words list to the SQL injection detection notification on adding/saving.

## [1.12.7] - 2022-10-24

- Changed checks against numeric ids due to conflict on previous evaluations.

## [1.12.6] - 2022-09-24

- Added input sanitization and attack checks.

## [1.12.5] - 2022-03-16

- Refactored IP Geolocation functions.

## [1.12.4] - 2021-12-17

- Input sanitization on search by tag.

## [1.12.3] - 2020-05-05

- Added extension point and params treating possibility on the repository's comments for single post builder.
- Fixed misplaced messages in English language file.
- Fixed typos in Spanish language file.

## [1.12.2] - 2020-03-17

- Tuned checks for showing comments admission on posts.

## [1.12.1] - 2019-12-08

- Added login form for unauthenticated users accessing the records browser.

## [1.12.0] - 2018-03-16

- Upgraded Google ReCaptcha lib to v2.

## [1.11.8] - 2018-01-24

- Added support for shortcode and media tag conversion calls in contents.

## [1.11.7] - 2017-12-18

- Added extension point on the comment addition script.
- Added missing module infos.
- Added check on comment saving to allow empty messages but attached reaction images.

## [1.11.6] - 2017-12-14

- Changed some visibility on some repository class methods to allow access by extender classes.
- Added check on comment addition to support remote post.

## [1.11.5] - 2017-11-18

- Added filters for settings that depend on other modules.

## [1.11.4] - 2017-08-18

- Fixed issue with hashtags autolinking.

## [1.11.3] - 2017-07-29

- Added check to avoid rendering in unpublished posts.

## [1.11.2] - 2017-07-11

- Added overriding checkpoint on `single_post_after_contents` extender.
- Other minor additions.

## [1.11.1] - 2017-06-16

- Added extension points to the repository.

## [1.11.0] - 2017-06-12

- Removed mandatory captcha usage.

## [1.10.3] - 2017-06-08

- Improved checks to prevent guests from commenting

## [1.10.2] - 2017-05-25

- Added extension point before emptying the trash.

## [1.10.1] - 2017-05-23

- Added per column function hook for extra details. 

## [1.10.0] - 2017-05-20

- Added extension points on the toolbox.
- Added missing parameter for Triklet redirector.
- Added extension points on the browser.
- Added helper method to repository class.

## [1.9.1] - 2017-05-17

- Fixes and additions to anonymous restrictions.

## [1.9.0] - 2017-05-16

- Added switch to avoid comments from anonymous users.

## [1.8.11] - 2017-05-01

- Fixed error thrown when comments contained quotes when checking against user limits.

## [1.8.10] - 2017-04-29

- Added checks to avoid errors by comments on deleted posts on the toolbox and the browser.
- Minimal fixes.

## [1.8.9] - 2017-04-19

- Removed propagated media deletions when trashing comments.

## [1.8.8] - 2017-04-11

- Added extenders for reporting comments to tickets instead of the contact form
  if Triklet is installed and enabled.

## [1.8.7] - 2017-04-03

- Added Changelog.
- Added extension point for custom IP source info on the records browser.

## [1.8.6] - 2017-03-22

- Detached embedded dependency on security module.

## [1.8.5] - 2017-03-14

- Rebranded group name to support automatic updates.
