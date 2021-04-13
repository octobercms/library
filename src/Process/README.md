## Rain Processes

Provides classes for executing console processes, such as git and composer.

### Composer

    // New instance
    $composer = new October\Rain\Process\Composer;

    // Returns an array of installed packages
    $composer->listPackages();

### Git

    // New instance
    $git = new October\Rain\Process\Git;

    // Push staged changes
    $git->push();
