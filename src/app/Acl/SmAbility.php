<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Acl;

final class SmAbility
{
    public const string INIT = 'storage-manager.init';

    public const string VIEW = 'storage-manager.view';

    public const string SEARCH = 'storage-manager.search';

    public const string CREATE_FILE = 'storage-manager.create-file';

    public const string CREATE_DIRECTORY = 'storage-manager.create-directory';

    public const string DELETE = 'storage-manager.delete';

    public const string RENAME = 'storage-manager.rename';

    public const string BULK_RENAME = 'storage-manager.bulk-rename';

    public const string COPY = 'storage-manager.copy';

    public const string MOVE = 'storage-manager.move';

    public const string ZIP = 'storage-manager.zip';

    public const string UNZIP = 'storage-manager.unzip';

    public const string UPLOAD_INIT = 'storage-manager.upload.init';

    public const string UPLOAD_CHUNK = 'storage-manager.upload.chunk';

    public const string UPLOAD_COMPLETE = 'storage-manager.upload.complete';

    public const string UPLOAD_STATUS = 'storage-manager.upload.status';

    public const string TRASH_LIST = 'storage-manager.trash.list';

    public const string TRASH_RESTORE = 'storage-manager.trash.restore';

    public const string TRASH_PURGE = 'storage-manager.trash.purge';

    public const string TRASH_EMPTY = 'storage-manager.trash.empty';

    public const string DISKS_LIST = 'storage-manager.disks.list';

    public const string DISKS_SELECT = 'storage-manager.disks.select';

    public const string DISKS_STATS = 'storage-manager.disks.stats';

    /**
     * @var list<string>
     */
    public const array ALL = [
        self::INIT,
        self::VIEW,
        self::SEARCH,
        self::CREATE_FILE,
        self::CREATE_DIRECTORY,
        self::DELETE,
        self::RENAME,
        self::BULK_RENAME,
        self::COPY,
        self::MOVE,
        self::ZIP,
        self::UNZIP,
        self::UPLOAD_INIT,
        self::UPLOAD_CHUNK,
        self::UPLOAD_COMPLETE,
        self::UPLOAD_STATUS,
        self::TRASH_LIST,
        self::TRASH_RESTORE,
        self::TRASH_PURGE,
        self::TRASH_EMPTY,
        self::DISKS_LIST,
        self::DISKS_SELECT,
        self::DISKS_STATS,
    ];

    /**
     * @var list<string>
     */
    public const array MUTATIONS = [
        self::CREATE_FILE,
        self::CREATE_DIRECTORY,
        self::DELETE,
        self::RENAME,
        self::BULK_RENAME,
        self::COPY,
        self::MOVE,
        self::ZIP,
        self::UNZIP,
        self::UPLOAD_INIT,
        self::UPLOAD_CHUNK,
        self::UPLOAD_COMPLETE,
        self::TRASH_RESTORE,
        self::TRASH_PURGE,
        self::TRASH_EMPTY,
    ];

    public static function isMutation(string $ability): bool
    {
        return in_array($ability, self::MUTATIONS, true);
    }
}
