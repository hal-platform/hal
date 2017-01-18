# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/).

## [Unreleased]

Sections: (`Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`)

## [2.9.0] - 2017-01-20

### Added
- Add new deployment type: `script`.
    - A new type of deployment can be used which runs scripts defined in the application's `.hal9000.yml`.
    - Data can be passed to these scripts using **Script Context** defined for the **Target**.
- Administrators can now remove other administrators without requiring direct database changes.
- More details on deployments are logged for **CodeDeploy** deployment types.
- Administrators and application leads can now edit the github repository user by an application directly within the UI.
- Improvements to UI on application page.

### Removed
- Remove EC2 autoscaling deployment type.

### Fixed
- Fix broken endpoint: `/api/users`.
- Fix periodic, unavoidable failures when docker starts a container for application builds.
- Prevent event logs from being duplicated while a build/push is in progress.
- Commits linking to refs (and vice versa) on the build page has been fixed.
- Fix stale data in cached deployment targets.

## [2.8.1] - 2016-04-06

### Fixed
- Dash accounts now supported.

## [2.8.0] - 2016-02-12

### Added
- Add ability to automatically deploy successful builds.
- Users can now favorite applications to find them easier.
- Multiple docker containers can be used within an application's build process.
- Add API documentation.
- Automatically select an environment for building from user preferences.
- Add more descriptive event messages for build commands.

### Fixed
- Fix unresponsive build menu in Chrome.
- Fix many UI issues in Good Browser.

## [2.7.0] - 2015-09-25

### Added
- **AWS CodeDeploy** is now supported.
- **Kraken** configuration is now batch encrypted for improved performance.

### Fixed
- Fixed **Elastic Beanstalk** deployments broken by AWS SDK V2 to V3 migration.
- Prevent missing temp directory from causing jobs to fail after agent is restarted.

## [2.6.4] - 2015-08-13

No release notes available.

## [2.6.3] - 2015-08-05

No release notes available.

## [2.6.2] - 2015-07-24

No release notes available.

## [2.6.1] - 2015-07-09

No release notes available.

## [2.6.0] - 2015-06-18

No release notes available.

## [2.5.0] - 2015-05-22

No release notes available.

## [2.4.2] - 2015-03-30

No release notes available.

## [2.4.1] - 2015-03-13

No release notes available.

## [2.4.0] - 2015-02-20

No release notes available.

## [2.3.4] - 2015-02-16

No release notes available.

## [2.3.3] - 2015-02-09

No release notes available.

## [2.3.2] - 2015-01-05

No release notes available.

## [2.3.1] - 2014-12-17

No release notes available.

## [2.3.0] - 2014-12-05

No release notes available.

## [2.2.0] - 2014-10-10

No release notes available.

## [2.1.1] - 2014-08-15

No release notes available.

## [2.1.0] - 2014-07-03

No release notes available.

## [2.0.1] - 2014-06-16

No release notes available.

## [2.0.0] - 2014-06-13

No release notes available.

## [1.2.0] - 2014-06-05

No release notes available.

## [1.1.1] - 2014-03-27

No release notes available.

## [1.1.0] - 2014-03-27

No release notes available.

## [1.0.0] - 2014-??-??

No release notes available.
