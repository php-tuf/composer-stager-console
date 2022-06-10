# Example Composer Stager Console Command

 This repository provides a reference implementation of [Composer Stager](https://github.com/php-tuf/composer-stager) as a console command for use via a terminal or a cron job. It is provides for illustration purposes only; **it is unsupported**.

* [Warning!](#warning)
* [Console command](#console-command)
* [As an example of implementation](#as-an-example-of-implementation)

## Warning!

This repository is for illustration only. **It is unsupported.** There are no plans to release it as an official, supported package unless the community indicates adequate demand. Furthermore, it is based on a pre-release version of [Composer Stager](https://github.com/php-tuf/composer-stager/tree) and may not exactly reflect its current state. [Consult that project](https://github.com/php-tuf/composer-stager) for the most current integration details.

## Console command

The console command is used by installing it via Git and invoking its executable:

```shell
$ git clone https://github.com/php-tuf/composer-stager-console.git
$ php composer-stager-console/bin/composer-stage
```

### Available commands

* `begin` - Begins the staging process by copying the active directory to the staging directory.
* `stage` - Executes a Composer command in the staging directory.
* `commit` - Makes the staged changes live by syncing the active directory with the staging directory.
* `clean` - Removes the staging directory.

### Example workflow:

```shell
# Copy the codebase to the staging directory.
$ bin/composer-stage begin

# Run a Composer command on it.
$ bin/composer-stage stage -- require example/package --update-with-all-dependencies

# Sync the changes back to the active directory.
$ bin/composer-stage commit --no-interaction

# Remove the staging directory.
$ bin/composer-stage clean --no-interaction
```

## As an example of implementation

This repository provides the simplest implementation possible in its context. The fact that it is a Symfony Console application is incidental but does require certain scaffolding that is beside the point. The important files for the purpose of illustration are these:

* [`composer.json`](composer.json) illustrates how Composer Stager is added to your application via Composer. The only really important value is [`require.php-tuf/composer-stager`](composer.json#L17). Add it to your Composer config with this command in the terminal:

      composer require php-tuf/composer-stager

* [`config/services.yml`](config/services.yml) illustrates injecting dependencies via a service container—in this case, Symfony's.
* The command classes illustrate invoking the Composer Stager code API:
  * [`BeginCommand`](src/Console/Command/BeginCommand.php)
  * [`StageCommand`](src/Console/Command/StageCommand.php)
  * [`CommitCommand`](src/Console/Command/CommitCommand.php)
  * [`CleanCommand`](src/Console/Command/CleanCommand.php)
