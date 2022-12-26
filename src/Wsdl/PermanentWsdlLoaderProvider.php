<?php
declare(strict_types=1);

namespace Soap\ExtSoapEngine\Wsdl;

use Exception;
use RuntimeException;
use Soap\ExtSoapEngine\Wsdl\Loader\WsdlLoaderInterface;
use Soap\ExtSoapEngine\Wsdl\Naming\Md5Strategy;
use Soap\ExtSoapEngine\Wsdl\Naming\NamingStrategy;

final class PermanentWsdlLoaderProvider implements WsdlProvider
{
    /**
     * @var bool
     */
    private $downloadForced = false;

    private $loader;
    private $namingStrategy = null;
    private $cacheDir = null;

    public function __construct(
        WsdlLoaderInterface $loader,
        ?NamingStrategy $namingStrategy = null,
        ?string $cacheDir = null
    ) {
        $this->loader = $loader;
        $this->namingStrategy = $namingStrategy;
        $this->cacheDir = $cacheDir;
    }

    public function __invoke(string $location): string
    {
        $cacheDir = $this->cacheDir ?? sys_get_temp_dir();
        $namingStrategy = $this->namingStrategy ?? new Md5Strategy();
        $file = $cacheDir . DIRECTORY_SEPARATOR . $namingStrategy($location);

        clearstatcache();

        try {
            $is_file = is_file($file);
            $exist_file = file_exists($file);

            if (!$this->downloadForced && $is_file && $exist_file) {
                return $file;
            }

            if (!$is_file && $exist_file) {
                throw new RuntimeException(sprintf('Path "%s" does not point to a file.', $file));
            }

            if ($exist_file && !is_writable($file)) {
                throw new RuntimeException(sprintf('File "%s" is not writable.', $file));
            }

            $handle = fopen($file, 'wb');

            if ($handle === false) {
                $error = error_get_last();
                $message = $error['message'] ?? 'Unable to open resource.';
                throw new RuntimeException($message);
            }

            $lock = @flock($handle, LOCK_EX, $would_block);

            if ($would_block) {
                throw new RuntimeException(sprintf('File "%s" is already locked.', $file));
            }

            if (!$lock) {
                throw new RuntimeException(sprintf('Could not acquire exclusive lock for "%s".', $file));
            }

            $write = @fwrite($handle, ($this->loader)($location));

            if ($write === false) {
                $error = error_get_last();

                throw new RuntimeException($error['message'] ?? 'unknown error.');
            }

            $close = @fclose($handle);

            if ($close === false) {
                $error = error_get_last();

                throw new RuntimeException($error['message'] ?? 'unknown error.');
            }

            clearstatcache();
        } catch (Exception $previous) {
            throw new RuntimeException(sprintf('Failed to write to file "%s".', $file), 0, $previous);
        }

        return $file;
    }

    /**
     * Makes it possible to refresh permanently stored WSDL files.
     */
    public function forceDownload(): self
    {
        $new = clone $this;
        $new->downloadForced = true;

        return $new;
    }
}
