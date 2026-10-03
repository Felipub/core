<p align="center">
    <a href="https://gibbonedu.org/" target="_blank"><img width="200" src="https://gibbonedu.org/img/gibbon-logo.png"></a><br>
    Gibbon is a flexible, open source school management platform designed <br>
    to make life better for teachers, students, parents and schools.
</p>

------

Gibbon Core
===========
The Core repository represents the bulk of Gibbon, including all of its primary functionality. The core can be extended through the use of modules and themes, which are provided separately. See the [Extend](https://gibbonedu.org/extend/) page for more info.

Gibbon is open source, and maintained for the benefit of teachers, students, parents and schools.

# SHEF Gibbon Customisation

This repository contains customisations of GibbonEdu for shef.ngo.

The project is based on GibbonEdu/core:
https://github.com/GibbonEdu/core

Customisations for shef.ngo are maintained in this repository.

## Development baseline

`master` in [Felipub/core](https://github.com/Felipub/core) is the official starting point for development. It incorporates the code running on the SHEF server, downloaded on **2026-10-03**, including the Gyansetu theme and deployed custom modules.

The server snapshot is preserved by the tag `baseline-server-2026-10-03-complete`. See [the baseline record](docs/BASELINE-2026-10-03.md) for its contents, verification and exclusions. Configuration, credentials, uploaded files and the live database must be supplied separately for a development environment.

The earlier GibbonEdu v23 adaptation and upstream update work are on hold. Historical branches remain available, but new work starts from `master`; upstream updates require a separate, deliberate review.

From an existing clone with a clean working tree, start a new task with:

```sh
git fetch origin
git switch -c feature/your-task origin/master
```

Push your feature branch and open a pull request against `Felipub/core:master`. Existing work on other branches can be reviewed and ported selectively onto this baseline.

## Documentation

For general upstream documentation, visit [docs.gibbonedu.org](https://docs.gibbonedu.org/). SHEF customisations may differ from upstream behaviour.

## Installation & Support

For installation instructions, visit [Getting Started: Installing Gibbon](https://docs.gibbonedu.org/administrators/getting-started/installing-gibbon/)

For support visit [ask.gibbonedu.org](https://ask.gibbonedu.org/) or see [our documentation](https://docs.gibbonedu.org/).

## Contributing

For SHEF development, use feature branches and pull requests against `master` as described above. The upstream project also provides these contribution references:

- [**Contributor Guide**](https://github.com/GibbonEdu/core/blob/master/.github/CONTRIBUTING.md) - Learn more about how you can contribute to Gibbon, from code to non-code contributions alike.

- [**Code of Conduct**](https://github.com/GibbonEdu/core/blob/master/.github/CODE_OF_CONDUCT.md) - Our pledge to foster a welcoming community and a positive environment for anyone to participate in.

- [**Developer Workflow**](https://docs.gibbonedu.org/developers/getting-started/developer-workflow/) - General guidance for contributing to upstream GibbonEdu; the SHEF branch workflow is documented above.

## License

Gibbon is licensed under GNU General Public License v3.0. You can obtain a copy of the license [here](https://github.com/GibbonEdu/core/blob/master/LICENSE).
