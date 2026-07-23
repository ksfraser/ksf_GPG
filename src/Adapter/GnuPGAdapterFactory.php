<?php
declare(strict_types=1);

namespace ksfraser\GPG\Adapter;

use ksfraser\GPG\Contracts\GnuPGAdapterInterface;

/**
 * GnuPG Adapter Factory
 * 
 * Automatically selects the best available adapter:
 * 1. CryptGpgAdapter (pear/crypt_gpg) - preferred
 * 2. CliGpgAdapter (exec fallback) - fallback
 * 
 * @since 1.0.0
 */
class GnuPGAdapterFactory
{
    /**
     * Create the best available adapter.
     *
     * @return GnuPGAdapterInterface
     *
     * @since 1.0.0
     */
    public static function create(): GnuPGAdapterInterface
    {
        if (CryptGpgAdapter::isAvailable()) {
            return new CryptGpgAdapter();
        }
        
        return new CliGpgAdapter();
    }

    /**
     * Create a specific adapter.
     *
     * @param string $adapterClass
     * @return GnuPGAdapterInterface
     *
     * @since 1.0.0
     */
    public static function createSpecific(string $adapterClass): GnuPGAdapterInterface
    {
        return new $adapterClass();
    }
}
