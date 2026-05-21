<?php

declare(strict_types=1);

namespace Drupal\example_demo\Controller;

use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Symfony\Component\HttpFoundation\Response;

final readonly class DynamicImageStyleDemo {

  public function __construct(
    private DynamicImageStyle $dynamicImageStyle,
  ) {}

  public function __invoke(): Response {
    $uri = 'public://pizza-umami.jpg';

    $examples = [
      // Scale — proportional resizing by width, height, or both.
      'Scale width 300' => [
        ['image_scale', ['width' => 300]],
      ],
      'Scale height 200' => [
        ['image_scale', ['height' => 200]],
      ],
      'Scale 300x200' => [
        ['image_scale', ['width' => 300, 'height' => 200]],
      ],
      'Scale 150x150' => [
        ['image_scale', ['width' => 150, 'height' => 150]],
      ],
      'Scale 600x400' => [
        ['image_scale', ['width' => 600, 'height' => 400]],
      ],
      'Scale upscale 1200' => [
        ['image_scale', ['width' => 1200, 'upscale' => TRUE]],
      ],

      // Resize — exact dimensions (stretches if aspect ratio differs).
      'Resize 200x200 (stretch)' => [
        ['image_resize', ['width' => 200, 'height' => 200]],
      ],
      'Resize 400x150 (stretch)' => [
        ['image_resize', ['width' => 400, 'height' => 150]],
      ],

      // Scale & Crop — scales then crops to exact dimensions.
      'Scale & Crop 300x300' => [
        ['image_scale_and_crop', ['width' => 300, 'height' => 300]],
      ],
      'Scale & Crop 400x150' => [
        ['image_scale_and_crop', ['width' => 400, 'height' => 150]],
      ],
      'Tiny thumbnail 50x50' => [
        ['image_scale_and_crop', ['width' => 50, 'height' => 50]],
      ],
      'Wide banner 800x200' => [
        ['image_scale_and_crop', ['width' => 800, 'height' => 200]],
      ],

      // Crop — cuts a region from a specific anchor point.
      'Crop 200x200 center' => [
        ['image_crop', ['width' => 200, 'height' => 200, 'anchor' => 'center-center']],
      ],
      'Crop 300x200 top-left' => [
        ['image_crop', ['width' => 300, 'height' => 200, 'anchor' => 'left-top']],
      ],
      'Crop 300x200 bottom-right' => [
        ['image_crop', ['width' => 300, 'height' => 200, 'anchor' => 'right-bottom']],
      ],

      // Rotate — rotates the image by given degrees.
      'Rotate 90°' => [
        ['image_rotate', ['degrees' => 90, 'bgcolor' => '']],
      ],
      'Rotate 180°' => [
        ['image_rotate', ['degrees' => 180, 'bgcolor' => '']],
      ],
      'Rotate 45° white bg' => [
        ['image_rotate', ['degrees' => 45, 'bgcolor' => '#FFFFFF']],
      ],
      'Rotate 15° transparent' => [
        ['image_rotate', ['degrees' => 15, 'bgcolor' => '']],
      ],

      // Desaturate — converts to grayscale.
      'Desaturate' => [
        ['image_desaturate', []],
        ['image_scale', ['width' => 400]],
      ],

      // Convert — changes image format.
      'Convert to WebP' => [
        ['image_scale', ['width' => 400]],
        ['image_convert', ['extension' => 'webp']],
      ],
      'Convert to PNG' => [
        ['image_scale', ['width' => 400]],
        ['image_convert', ['extension' => 'png']],
      ],

      // Multi-effect chains — combining several effects in sequence.
      'Desaturate + Scale 300' => [
        ['image_desaturate', []],
        ['image_scale', ['width' => 300]],
      ],
      'Rotate 90° + Scale 200' => [
        ['image_rotate', ['degrees' => 90, 'bgcolor' => '']],
        ['image_scale', ['width' => 200]],
      ],
      'Rotate 45° + Scale 300' => [
        ['image_rotate', ['degrees' => 45, 'bgcolor' => '#FFFFFF']],
        ['image_scale', ['width' => 300]],
      ],
      'Convert WebP → Scale 300' => [
        ['image_convert', ['extension' => 'webp']],
        ['image_scale', ['width' => 300]],
      ],
      'Desaturate + Crop 250x250 + WebP' => [
        ['image_desaturate', []],
        ['image_scale_and_crop', ['width' => 250, 'height' => 250]],
        ['image_convert', ['extension' => 'webp']],
      ],
      'Rotate 10° + Crop 300x300 + WebP' => [
        ['image_rotate', ['degrees' => 10, 'bgcolor' => '#FFFFFF']],
        ['image_scale_and_crop', ['width' => 300, 'height' => 300]],
        ['image_convert', ['extension' => 'webp']],
      ],
      'Scale 500 + Desaturate + Rotate 5°' => [
        ['image_scale', ['width' => 500]],
        ['image_desaturate', []],
        ['image_rotate', ['degrees' => 5, 'bgcolor' => '#000000']],
      ],

      // Contrib: image_effects module — Set Canvas.
      'Canvas 400x400 orange' => [
        ['image_effects_set_canvas', [
          'canvas_size' => 'exact',
          'canvas_color' => '#FF6600FF',
          'exact' => ['width' => 400, 'height' => 400, 'placement' => 'center-center', 'x_offset' => 0, 'y_offset' => 0],
        ],
        ],
      ],
      'Scale 200 + Canvas 300x300 dark' => [
        ['image_scale', ['width' => 200, 'height' => 200]],
        ['image_effects_set_canvas', [
          'canvas_size' => 'exact',
          'canvas_color' => '#2D2D2DFF',
          'exact' => ['width' => 300, 'height' => 300, 'placement' => 'center-center', 'x_offset' => 0, 'y_offset' => 0],
        ],
        ],
      ],
      'Canvas relative padding 20px' => [
        ['image_scale', ['width' => 250]],
        ['image_effects_set_canvas', [
          'canvas_size' => 'relative',
          'canvas_color' => '#3498DBFF',
          'relative' => ['left' => 20, 'right' => 20, 'top' => 20, 'bottom' => 20],
        ],
        ],
      ],
      'Canvas top-left placement' => [
        ['image_scale', ['width' => 150]],
        ['image_effects_set_canvas', [
          'canvas_size' => 'exact',
          'canvas_color' => '#E74C3CFF',
          'exact' => ['width' => 300, 'height' => 300, 'placement' => 'left-top', 'x_offset' => 0, 'y_offset' => 0],
        ],
        ],
      ],
      'Canvas asymmetric padding' => [
        ['image_scale', ['width' => 250]],
        ['image_effects_set_canvas', [
          'canvas_size' => 'relative',
          'canvas_color' => '#2ECC71FF',
          'relative' => ['left' => 40, 'right' => 40, 'top' => 10, 'bottom' => 10],
        ],
        ],
      ],

      // Contrib: image_effects module — Watermark.
      'Watermark bottom-right' => [
        ['image_scale', ['width' => 400]],
        ['image_effects_watermark', [
          'watermark_image' => 'public://umami-bundle.png',
          'watermark_width' => 80,
          'watermark_height' => NULL,
          'placement' => 'bottom-right',
          'x_offset' => -10,
          'y_offset' => -10,
          'opacity' => 70,
        ],
        ],
      ],
      'Watermark top-left' => [
        ['image_scale', ['width' => 400]],
        ['image_effects_watermark', [
          'watermark_image' => 'public://umami-bundle.png',
          'watermark_width' => 60,
          'watermark_height' => NULL,
          'placement' => 'left-top',
          'x_offset' => 10,
          'y_offset' => 10,
          'opacity' => 90,
        ],
        ],
      ],
      'Watermark center + Desaturate' => [
        ['image_desaturate', []],
        ['image_scale', ['width' => 400]],
        ['image_effects_watermark', [
          'watermark_image' => 'public://umami-bundle.png',
          'watermark_width' => 150,
          'watermark_height' => NULL,
          'placement' => 'center-center',
          'x_offset' => 0,
          'y_offset' => 0,
          'opacity' => 40,
        ],
        ],
      ],

      // Complex combinations.
      'Canvas + Desaturate + WebP' => [
        ['image_desaturate', []],
        ['image_scale', ['width' => 200]],
        ['image_effects_set_canvas', [
          'canvas_size' => 'exact',
          'canvas_color' => '#000000FF',
          'exact' => ['width' => 300, 'height' => 300, 'placement' => 'center-center', 'x_offset' => 0, 'y_offset' => 0],
        ],
        ],
        ['image_convert', ['extension' => 'webp']],
      ],
      'Watermark + Canvas + WebP' => [
        ['image_scale', ['width' => 300]],
        ['image_effects_watermark', [
          'watermark_image' => 'public://umami-bundle.png',
          'watermark_width' => 60,
          'watermark_height' => NULL,
          'placement' => 'bottom-right',
          'x_offset' => -5,
          'y_offset' => -5,
          'opacity' => 80,
        ],
        ],
        ['image_effects_set_canvas', [
          'canvas_size' => 'relative',
          'canvas_color' => '#1A1A1AFF',
          'relative' => ['left' => 15, 'right' => 15, 'top' => 15, 'bottom' => 15],
        ],
        ],
        ['image_convert', ['extension' => 'webp']],
      ],
      'Crop + Rotate + Desaturate + PNG' => [
        ['image_scale_and_crop', ['width' => 300, 'height' => 300]],
        ['image_rotate', ['degrees' => 15, 'bgcolor' => '#FFFFFF']],
        ['image_desaturate', []],
        ['image_convert', ['extension' => 'png']],
      ],
    ];

    $html = '<html><head><meta charset="utf-8">';
    $html .= '<title>Dynamic Image Style Demo</title>';
    $html .= '<style>';
    $html .= 'body { font-family: system-ui, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }';
    $html .= 'h1 { color: #333; }';
    $html .= '.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }';
    $html .= '.card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }';
    $html .= '.card h3 { margin: 0 0 12px; font-size: 14px; color: #555; }';
    $html .= '.card img { max-width: 100%; height: auto; display: block; border-radius: 4px; }';
    $html .= '.card code { display: block; margin-top: 8px; font-size: 11px; color: #888; word-break: break-all; }';
    $html .= '</style></head><body>';
    $html .= '<h1>Dynamic Image Style Demo</h1>';
    $html .= '<p>Source: <code>' . $uri . '</code></p>';
    $html .= '<div class="grid">';

    foreach ($examples as $label => $effects) {
      $url = $this->dynamicImageStyle->buildUrl($uri, $effects);
      $html .= '<div class="card">';
      $html .= '<h3>' . $label . '</h3>';
      $html .= '<img src="' . $url . '" alt="' . $label . '" loading="lazy">';
      $html .= '<code>' . $url . '</code>';
      $html .= '</div>';
    }

    $html .= '</div></body></html>';

    return new Response($html);
  }

}
