
## Setting up Pegass

Directories [prod](../../deploy/prod) and [preprod](../../deploy/preprod) 
contain a `dotenv` file that you need to fill up with the right configuration.

Pegass is the Red Cross volunteers directory.

**Objective**:
We need to keep RedCall database aligned to the Red Cross directory, for
example if a new volunteer joins a unit, if a volunteer quits it, if a
volunteer does trainings and gains some skills, etc.

This synchronization can not be done manually by the units themselves,
we tried at the beginning to maintain a google spreadsheet aligned, but
this creates some backlog, inconsistencies and after a few months,
it makes redcall not usable (you can see some history on the git repo).

If you don't have Pegass credentials, you can still use the development
database of fake volunteers in order to do your tests.

### Project configuration

- Set `PEGASS_LOGIN` with your pegass login

- Set `PEGASS_PASSWORD` with your password

[Go back](../../README.md)
