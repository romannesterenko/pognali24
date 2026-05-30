<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load([
                'profile',
                'driverProfile',
                'cars',
            ])
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ]);

        $profile = $request->user()->profile;

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'profile' => $profile->fresh(),
        ]);
    }

    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => [
                'required',
                'max:20240',
                'mimes:jpg,jpeg,png,webp,heic,heif',
            ],
        ]);

        $profile = $request->user()->profile;

        if ($profile->avatar) {
            Storage::disk('public')->delete($profile->avatar);
        }

        $file = $request->file('avatar');

        $filename = Str::uuid() . '.webp';

        $inputPath = $file->getRealPath();

        $image = new \Imagick($inputPath);

        // приводим к квадрату (аватар)
        $image->cropThumbnailImage(300, 300);

        // оптимизация
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(85);

        $outputPath = storage_path('app/public/avatars/' . $filename);

        $image->writeImage($outputPath);
        $image->clear();
        $image->destroy();

        $profile->update([
            'avatar' => 'avatars/' . $filename,
        ]);

        return response()->json([
            'success' => true,
            'avatar_url' => $profile->fresh()->avatar_url,
        ]);
    }

    /*public function avatar(Request $request): JsonResponse
    {

        $request->validate([
            'avatar' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:20480',
            ],
        ]);

        $filename = Str::uuid() . '.webp';
        $upload = $request->file('avatar');

        $manager = new ImageManager(new Driver());

        $image = $manager->read($upload);



        $image->scaleDown(width: 500);
        $image = $manager->make($file);

        return response()->json([
            'avatar' => $image
        ]);




        $profile = $request->user()->profile;
        $file = $request->file('avatar');

        if ($profile->avatar) {
            Storage::disk('public')->delete($profile->avatar);
        }

        $filename = Str::uuid() . '.webp';

        // Синтаксис для версии 3.x
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($file);

        // Уменьшаем размер с сохранением пропорций
        $image->resize(500, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Конвертируем в WebP
        $image->encode('webp', 80);

        // Сохраняем
        Storage::disk('public')->put(
            'avatars/' . $filename,
            (string) $image
        );

        $profile->update([
            'avatar' => 'avatars/' . $filename,
        ]);

        return response()->json([
            'success' => true,
            'avatar_url' => $profile->fresh()->avatar_url,
        ]);
    }*/

}
