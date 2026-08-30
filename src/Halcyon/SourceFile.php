<?php namespace October\Rain\Halcyon;

use Storage;
use October\Rain\Database\Model;
use Exception;

/**
 * SourceFile is an Eloquent model for storing source files (assets, blueprints,
 * language files, and other non-compound theme/app files) in the database.
 *
 * A row represents one file, identified by (source, path). The bytes are stored
 * either inline in the `content` column, or by reference to a Laravel Storage
 * disk via `disk` + `disk_path`. The two modes are mutually exclusive and
 * inferred from whether `disk` is set.
 *
 * This is a sibling primitive to {@link \October\Rain\Halcyon\Model}, which
 * handles compound source files (templates with sections parsed by the
 * Processor). SourceFile handles non-compound files where the bytes are opaque.
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class SourceFile extends Model
{
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var string table associated with the model.
     */
    protected $table = 'source_files';

    /**
     * @var array fillable attributes that are mass assignable.
     */
    protected $fillable = [
        'source',
        'path',
        'content',
        'disk',
        'disk_path',
        'file_size',
        'mime_type',
    ];

    /**
     * @var array casts for attributes.
     */
    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * @var array dates that should be mutated to Carbon instances.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    //
    // Scopes & lookups
    //

    /**
     * scopeBySource constrains the query to a single source identifier.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * scopeByPath constrains the query to an exact path.
     */
    public function scopeByPath($query, string $path)
    {
        return $query->where('path', $path);
    }

    /**
     * scopeByPathPrefix constrains the query to paths beginning with the prefix.
     */
    public function scopeByPathPrefix($query, string $prefix)
    {
        return $query->where('path', 'like', rtrim($prefix, '/') . '/%');
    }

    /**
     * findByPath returns a single row matching the source and path, or null.
     */
    public static function findByPath(string $source, string $path): ?static
    {
        return static::query()->bySource($source)->byPath($path)->first();
    }

    /**
     * existsAt returns true if a row exists for the given source and path.
     */
    public static function existsAt(string $source, string $path): bool
    {
        return static::query()->bySource($source)->byPath($path)->exists();
    }

    /**
     * upsertAt writes inline content to the row matching the source and path,
     * creating one if missing. If a soft-deleted row exists at that key it is
     * restored rather than producing a duplicate.
     */
    public static function upsertAt(string $source, string $path, string $content): static
    {
        $row = static::withTrashed()->bySource($source)->byPath($path)->first();

        if ($row === null) {
            $row = new static([
                'source' => $source,
                'path' => $path,
            ]);
        }
        elseif ($row->trashed()) {
            $row->restore();
        }

        $row->setContents($content);
        $row->save();

        return $row;
    }

    /**
     * upsertOnDiskAt writes disk-backed content to the row matching the source
     * and path, creating one if missing. Mirrors upsertAt() including the
     * restore-over-tombstone semantics, but the bytes land on the given
     * Storage disk instead of the content column.
     */
    public static function upsertOnDiskAt(string $source, string $path, string $disk, string $diskPath, string $bytes, ?string $mimeType = null): static
    {
        $row = static::withTrashed()->bySource($source)->byPath($path)->first();

        if ($row === null) {
            $row = new static([
                'source' => $source,
                'path' => $path,
            ]);
        }
        elseif ($row->trashed()) {
            $row->restore();
        }

        $row->disk = $disk;
        $row->disk_path = $diskPath;

        if ($mimeType !== null) {
            $row->mime_type = $mimeType;
        }

        $row->setContents($bytes);
        $row->save();

        return $row;
    }

    /**
     * tombstoneAt creates a soft-deleted row at the given source and path so
     * that find/list calls report the file as not existing. Used to suppress
     * a filesystem fallback when there is no DB content to override it. If a
     * row already exists it is soft-deleted (or left soft-deleted).
     */
    public static function tombstoneAt(string $source, string $path): void
    {
        $row = static::withTrashed()->bySource($source)->byPath($path)->first();

        if ($row === null) {
            $row = new static([
                'source' => $source,
                'path' => $path,
                'content' => '',
                'file_size' => 0,
            ]);
            $row->save();
            $row->delete();
            return;
        }

        if (!$row->trashed()) {
            $row->delete();
        }
    }

    //
    // Content access (mode-aware)
    //

    /**
     * isDiskBacked returns true if this row stores its bytes on a Storage disk
     * rather than inline in the content column.
     */
    public function isDiskBacked(): bool
    {
        return $this->disk !== null && $this->disk !== '';
    }

    /**
     * getContents returns the file bytes, regardless of storage mode.
     */
    public function getContents(): string
    {
        if ($this->isDiskBacked()) {
            return (string) $this->getDisk()->get($this->disk_path);
        }

        return (string) $this->content;
    }

    /**
     * setContents writes the file bytes using whichever storage mode the row is
     * configured for. Disk mode requires `disk` and `disk_path` to already be
     * set on the model; inline mode just updates the `content` attribute. In
     * both cases `file_size` is recalculated. The row is not saved.
     */
    public function setContents(string $bytes): static
    {
        if ($this->isDiskBacked()) {
            if (!$this->disk_path) {
                throw new Exception('SourceFile disk_path must be set before writing disk-backed contents.');
            }

            $this->getDisk()->put($this->disk_path, $bytes);
            $this->content = null;
        }
        else {
            $this->content = $bytes;
        }

        $this->file_size = strlen($bytes);

        return $this;
    }

    //
    // Disk-mode helpers
    //

    /**
     * getDisk returns the configured Storage disk instance for this row.
     */
    public function getDisk()
    {
        if (!$this->isDiskBacked()) {
            throw new Exception('SourceFile is not disk-backed; no disk is configured.');
        }

        return Storage::disk($this->disk);
    }

    /**
     * getUrl returns the public URL for the file, or null when the row is
     * stored inline (inline rows have no public URL by default).
     */
    public function getUrl(): ?string
    {
        if (!$this->isDiskBacked()) {
            return null;
        }

        return $this->getDisk()->url($this->disk_path);
    }

    /**
     * getLocalPath returns an absolute local filesystem path to the file.
     *
     * For local disks the path on the disk is returned directly. For remote
     * disks the file is copied to a temporary local path and that path is
     * returned, allowing tools that require a local file (image processors,
     * mime detection, etc.) to operate on the bytes.
     */
    public function getLocalPath(): ?string
    {
        if (!$this->isDiskBacked()) {
            return null;
        }

        $disk = $this->getDisk();

        // Local disks expose path() returning an absolute filesystem path
        if (method_exists($disk, 'path')) {
            try {
                return $disk->path($this->disk_path);
            }
            catch (Exception $ex) {
                // Fall through to temp-copy path below
            }
        }

        return $this->copyToLocalTemp();
    }

    /**
     * getMimeType returns the stored mime type, or attempts to detect it from
     * the disk-backed file if not stored.
     */
    public function getMimeType(): ?string
    {
        if ($this->mime_type) {
            return $this->mime_type;
        }

        if ($this->isDiskBacked()) {
            try {
                return $this->getDisk()->mimeType($this->disk_path);
            }
            catch (Exception $ex) {
                return null;
            }
        }

        return null;
    }

    //
    // Lifecycle
    //

    /**
     * boot the model.
     */
    public static function boot()
    {
        parent::boot();

        // When a row is force-deleted, also remove its file from the disk.
        // Soft-deleted rows keep their file so they can be restored.
        static::deleted(function (self $row) {
            if (!$row->isReallyDeleting()) {
                return;
            }

            if (!$row->isDiskBacked()) {
                return;
            }

            try {
                $row->getDisk()->delete($row->disk_path);
            }
            catch (Exception $ex) {
                // Swallow: the row is already gone from the database, and we
                // don't want a missing-file error to throw post-delete.
            }
        });
    }

    /**
     * isReallyDeleting returns true when the model is being force-deleted (or
     * doesn't use soft deletes at all), as opposed to being soft-deleted.
     */
    protected function isReallyDeleting(): bool
    {
        return $this->forceDeleting ?? false;
    }

    //
    // Internal
    //

    /**
     * copyToLocalTemp streams a remote-disk file to a temporary local path and
     * returns that path. Subsequent calls reuse the same temp file as long as
     * the source has not changed.
     */
    protected function copyToLocalTemp(): string
    {
        $signature = md5($this->disk . ':' . $this->disk_path . ':' . ($this->updated_at?->timestamp ?? ''));
        $extension = pathinfo($this->disk_path, PATHINFO_EXTENSION);
        $tempName = $signature . ($extension ? '.' . $extension : '');
        $tempPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'source-files' . DIRECTORY_SEPARATOR . $tempName;

        if (!is_dir(dirname($tempPath))) {
            @mkdir(dirname($tempPath), 0755, true);
        }

        if (!file_exists($tempPath)) {
            file_put_contents($tempPath, $this->getContents());
        }

        return $tempPath;
    }
}
