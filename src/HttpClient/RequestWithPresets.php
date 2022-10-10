<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Pgyf\Opensdk\Kernel\Exceptions\InvalidArgumentException;
use Pgyf\Opensdk\Kernel\Support\Str;

use function array_merge;
use function in_array;
use function is_file;
use function is_string;
use function strtoupper;
use function substr;

trait RequestWithPresets
{
    /**
     * @var array<string, string>
     */
    protected $prependHeaders = [];

    /**
     * @var array<string, mixed>
     */
    protected $prependParts = [];

    /**
     * @var array<string, mixed>
     */
    protected $presets = [];

    /**
     * @param  array<string, mixed>  $presets
     * @return static
     */
    public function setPresets(array $presets): self
    {
        $this->presets = $presets;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return static
     */
    public function withHeader(string $key, string $value): self
    {
        $this->prependHeaders[$key] = $value;

        return $this;
    }

    /**
     * @param array $headers
     * @return static
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->withHeader($key, $value);
        }

        return $this;
    }

    /**
     * Undocumented function
     * @param string|array $key
     * @param mixed $value
     * @return static
     * @throws InvalidArgumentException
     */
    public function with($key, $value = null): self
    {
        if (\is_array($key)) {
            // $client->with(['appid', 'mchid'])
            // $client->with(['appid' => 'wx1234567', 'mchid'])
            foreach ($key as $k => $v) {
                if (\is_int($k) && is_string($v)) {
                    [$k, $v] = [$v, null];
                }

                $this->with($k, $v ?? $this->presets[$k] ?? null);
            }

            return $this;
        }

        $this->prependParts[$key] = $value ?? $this->presets[$key] ?? null;

        return $this;
    }

    // /**
    //  * Undocumented function
    //  * @param string $pathOrContents
    //  * @param string $formName
    //  * @param string|null $filename
    //  * @return static
    //  * @throws RuntimeException
    //  * @throws InvalidArgumentException
    //  */
    // public function withFile(string $pathOrContents, string $formName = 'file', string $filename = null): self
    // {
    //     $file = is_file($pathOrContents) ? File::fromPath(
    //         $pathOrContents,
    //         $filename
    //     ) : File::withContents($pathOrContents, $filename);

    //     /**
    //      * @var array{headers: array<string, string>, body: string}
    //      */
    //     $options = Form::create([$formName => $file])->toOptions();

    //     $this->withHeaders($options['headers']);

    //     return $this->withOptions([
    //         'body' => $options['body'],
    //     ]);
    // }

    // /**
    //  * Undocumented function
    //  * @param string $contents
    //  * @param string $formName
    //  * @param string|null $filename
    //  * @return static
    //  * @throws RuntimeException
    //  * @throws InvalidArgumentException
    //  */
    // public function withFileContents(string $contents, string $formName = 'file', string $filename = null): self
    // {
    //     return $this->withFile($contents, $formName, $filename);
    // }

    // /**
    //  * @param array $files
    //  * @return static
    //  * @throws RuntimeException
    //  * @throws InvalidArgumentException
    //  */
    // public function withFiles(array $files): self
    // {
    //     foreach ($files as $key => $value) {
    //         $this->withFile($value, $key);
    //     }

    //     return $this;
    // }

    public function mergeThenResetPrepends(array $options, string $method = 'GET'): array
    {
        $method = strtoupper($method);
        $name = in_array($method, ['GET', 'HEAD', 'DELETE']) ? 'query' : 'body';

        if (($options['headers']['Content-Type'] ?? $options['headers']['content-type'] ?? null) === 'application/json' || !empty($options['json'])) {
            $name = 'json';
        }

        if (($options['headers']['Content-Type'] ?? $options['headers']['content-type'] ?? null) === 'text/xml' || !empty($options['xml'])) {
            $name = 'xml';
        }

        if (!empty($this->prependParts)) {
            $options[$name] = array_merge($this->prependParts, $options[$name] ?? []);
        }

        if (!empty($this->prependHeaders)) {
            $options['headers'] = array_merge($this->prependHeaders, $options['headers'] ?? []);
        }

        $this->prependParts = [];
        $this->prependHeaders = [];

        return $options;
    }

    /**
     * @param string $method
     * @param mixed $value
     * @return static
     * @throws InvalidArgumentException
     */
    public function handleMagicWithCall(string $method, $value = null): self
    {
        // $client->withAppid();
        // $client->withAppid('wxf8b4f85f3a794e77');
        // $client->withAppidAs('sub_appid');
        if (!Str::startsWith($method, 'with')) {
            throw new InvalidArgumentException(sprintf('The method "%s" is not supported.', $method));
        }

        $key = Str::snakeCase(substr($method, 4));

        // $client->withAppidAs('sub_appid');
        if (Str::endsWith($key, '_as')) {
            $key = substr($key, 0, -3);

            [$key, $value] = [is_string($value) ? $value : $key, $this->presets[$key] ?? null];
        }

        return $this->with($key, $value);
    }
}
