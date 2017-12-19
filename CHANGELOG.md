
# Comments Module Change Log

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
