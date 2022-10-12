<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResizeImageManipulationRequest;
use App\Http\Resources\V1\ImageManipulationResource;
use App\Models\Album;
use App\Models\ImageManipulation;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;


class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ImageManipulationResource::collection(ImageManipulation::where('user_id', $request->user()->id)->paginate());
    }

    public function byAlbum(Request $request, Album $album)
    {
        if($request->user()->id != $album->user_id) {
            return abort(403, 'Unauthorized');
        }
        $where = [
            'album_id' => $album->id
        ];

        return ImageManipulationResource::collection(ImageManipulation::where($where)->paginate());
    }


    public function resize(ResizeImageManipulationRequest $request)
    {
        $all = $request->all();
        /**
         * @var UploadedFile|string $image
         */
        $image = $all['image'];
        unset($all['image']);

        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => $request->user()->id,
        ];

        if(isset($all['album_id'])) {
            $album = Album::find($all['album_id']);
            if($request->user()->id != $album->user_id) {
                return abort(403, 'Unauthorized');
            }
            $data['album_id'] = $all['album_id'];
        }

        $dir = 'images/' .Str::random() . '/';
        $absolutePath = public_path($dir);
        File::makeDirectory($absolutePath);

        if($image instanceof UploadedFile) {
            $data['name'] = $image->getClientOriginalName();
            $filename = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $originalPath = $absolutePath.$data['name'];

            $image->move($absolutePath, $data['name']);
        } else {
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath.$data['name'];

            copy($image, $originalPath);
        }
        $data['path'] = $dir.$data['name'];

        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height, $image) = $this->getImageWithAndHeight($w,$h, $originalPath);

        $resizedFilename = $filename.'-resized.' . $extension;

        $image->resize($width, $height)->save($absolutePath.$resizedFilename);
        $data['output_path'] = $dir . $resizedFilename;

        $imageManipulation = ImageManipulation::create($data);

        return new ImageManipulationResource($imageManipulation);
    }


    public function show(Request $request, ImageManipulation $image)
    {
        if($request->user()->id != $image->user_id) {
            return abort(403, 'Unauthorized');
        }
        return new ImageManipulationResource($image);
    }



    public function destroy(Request $request, ImageManipulation $image)
    {
        if($request->user()->id != $image->user_id) {
            return abort(403, 'Unauthorized');
        }
        $image->delete();
        return response('', 204);
    }

    protected function getImageWithAndHeight(mixed $w, mixed $h, string $originalPath)
    {
        // 1000 - 50% = 500px
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if(str_ends_with($w, '%')) {
            $ratioW = (float)str_replace('%', '', $w);
            $ratioH = $h ? (float)str_replace('%', '', $h) : $ratioW;

            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float)$w;

            $newHeight = $h ? (float)$h : $originalHeight * $newWidth/$originalWidth;
        }

        return [$newWidth, $newHeight, $image];
    }
}
