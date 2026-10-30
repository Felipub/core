Database integration tests use the application's MySqlConnector and real MySQL 5.7.
Each test starts a transaction and rolls it back, checking exact fixture IDs/values.
The bootstrap refuses non-disposable/non-Testing databases.
Run `lab.ps1 integration -Target master -Functional` after preparing and resetting the disposable environment.
