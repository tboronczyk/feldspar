<?php

declare(strict_types=1);

namespace Feldspar\Services;

class Avatar
{
  /**
   * @param int $accountId
   */
    public function createDefault(int $accountId): void
    {
        $colorSchemes = [
        [0xb71c1c, 0xe23e3e, 0xec8080, 0xf6c3c3],
        [0xff9800, 0xffb84d, 0xffd699, 0xfff5e5],
        [0x33691e, 0x50a52f, 0x75ce52, 0xa5df8e],
        [0x2734bd, 0x5560dc, 0x949be9, 0xd3d6f6],
        [0x6a1b9a, 0x9528d9, 0xb569e5, 0xd5aaf0],
        ];

        $imgSize = 256;
        $sqSize = 32;
        $numSquares = ceil($imgSize / $sqSize);

        $colorScheme = $colorSchemes[rand(0, count($colorSchemes) - 1)];
        $numColors = count($colorScheme) - 1;

        $img = imagecreatetruecolor($imgSize, $imgSize);

        for ($row = 0; $row < $numSquares; $row++) {
            for ($col = 0; $col < $numSquares; $col++) {
                $x = $row * $sqSize;
                $y = $col * $sqSize;
                imagefilledrectangle($img, $x, $y, $x + $sqSize, $y + $sqSize, $colorScheme[rand(0, $numColors)]);
            }
        }

        imagepng($img, getcwd() . '/avatars/' . $accountId . '.png');
    }

  /**
   * @param int $accountId
   * @param ?string $url
   */
    public function createFromUrl(int $accountId, ?string $url): void
    {
        if ($url === null) {
            $this->createDefault($accountId);
            return;
        }

        $img = imagecreatefromjpeg($url);
        if ($img === false) {
            $this->createDefault($accountId);
            return;
        }

        imagepng($img, getcwd() . '/avatars/' . $accountId . '.png');
    }
}
