Fixtures contain synthetic records only. No production rows, credentials or real photos.
`seed.php` extends the harness metadata/schema with named pupils, two teachers, families,
date boundaries and attendance. Student/parent login is disabled.
`deployed-schema.sql` contains only two missing DDL definitions extracted from the local backup;
the backup itself is not required or included. See ../README.md for provenance and isolation.
