<?php

namespace App\Services;

use Illuminate\Support\Uri;

final class UiAvatars
{
    protected array $query = [];

    public function __construct(protected string $name)
    {
        $this->query['name'] = $name;
        $this->query['background'] = '030712';
        $this->query['rounded'] = true;
    }

    public static function make(string $name): self
    {
        return resolve(self::class, ['name' => $name]);
    }

    public function size(int $size): self
    {
        $this->query['size'] = $size;

        return $this;
    }

    public function rounded(): self
    {
        $this->query['rounded'] = true;

        return $this;
    }

    /**
     * @param string $hexColor hex color code without the '#' sign
     *
     * @return $this
     */
    public function background(string $hexColor): self
    {
        $this->query['background'] = $hexColor;

        return $this;
    }

    /**
     * @param string $hexColor hex color code without the '#' sign
     *
     * @return $this
     */
    public function color(string $hexColor): self
    {
        $this->query['color'] = $hexColor;

        return $this;
    }

    /**
     * @param float|int $fontSize default 0.5
     *
     * @return $this
     */
    public function fontSize(float|int $fontSize): self
    {
        $this->query['font-size'] = $fontSize;

        return $this;
    }

    public function get(): string
    {
        // Need to follow formatting limitation from https://ui-avatars.com

        return Uri::of('https://ui-avatars.com/api/')
            ->withQuery([
                'name'       => urlencode((string) $this->query['name']),
                'size'       => $this->query['size'] ?? 64,
                'background' => $this->query['background'] ?? '030712',
                'color'      => $this->query['color'] ?? 'FFF',
                'font-size'  => $this->query['font-size'] ?? 0.5,
                'rounded'    => (bool) $this->query['rounded'],
            ])
            ->toStringable();
    }
}
