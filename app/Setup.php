<?php

namespace App;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Setup
{
    protected ?bool $localFilament = null;
    protected ?string $filamentPath = null;
    protected string $repo;
    protected string $issueNumber;
    protected string $branch;
    protected string $workingDirectory;
    protected ?bool $openInEditor = null;
    protected ?string $editor = null;
    protected string $initialDirectory;

    public function configure(bool $localFilament = false): static
    {
        $this->initialDirectory = getcwd();

        $this->repo = text(
            label: 'Repository',
            required: true,
            validate: fn (string $repo): ?string => match (true) {
                ! str_contains($repo, 'github') => 'The repository must be a valid GitHub repository.',
                default => null,
            },
        );

        $this->issueNumber = text(
            label: 'Issue number',
            required: true,
            validate: fn (string $issueNumber): ?string => match (true) {
                ! filter_var($issueNumber, FILTER_VALIDATE_INT) => 'The issue number must be a valid integer.',
                default => null,
            },
        );

        $this->branch = text(
            label: 'Branch',
            default: 'main',
        );

        if ($localFilament) {
            $this->localFilament = true;
            $this->filamentPath = text(
                label: 'Filament path',
                default: '../filament/filament/packages/*',
                required: true,
            );
        }

        $this->openInEditor = confirm(
            label: 'Open in editor?',
            default: true,
        );

        if ($this->openInEditor) {
            $editor = select(
                label: 'Editor',
                options: [
                    'VS Code',
                    'PHPStorm',
                ],
                default: 'PHPStorm',
            );

            $this->editor = match ($editor) {
                'VS Code' => 'code',
                'PHPStorm' => 'phpstorm',
            };
        }

        $this->workingDirectory = getcwd() . "/issue-" . $this->issueNumber;

        return $this;
    }

    public function getRepo(): string
    {
        return $this->repo;
    }

    public function getIssueNumber(): string
    {
        return $this->issueNumber;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function getInitialDirectory(): string
    {
        return $this->initialDirectory;
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function shouldOpenEditor(): bool
    {
        return $this->openInEditor ?? false;
    }

    public function getEditor(): string
    {
        return $this->editor ?? 'code';
    }

    public function isLocalFilament(): bool
    {
        return $this->localFilament ?? false;
    }

    public function getFilamentPath(): ?string
    {
        return $this->filamentPath ?? null;
    }
}
