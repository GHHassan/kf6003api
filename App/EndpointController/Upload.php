<?php

namespace App\EndpointController;

use App\{
    Request,
    ClientError
};

class Upload extends Endpoint
{
    private $uploadDir;
    protected $requestData;
    private $allowedParams = ['image', 'video'];
    private $allowedMethods = ['POST'];
    private $maxImageSize = 2 * 1024 * 1024; // 2MB
    private $maxVideoSize = 2 * 1024 * 1024; // 2MB

    public function __construct()
    {
        $this->checkAllowedMethod(Request::method(), $this->allowedMethods);
        $data = $this->uploadData();
        parent::__construct($data);
    }

    private function validateFileType($file, $allowedTypes)
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new ClientError(422, 'Invalid file type');
        }
    }

    public function uploadData()
    {
        if (isset($_FILES['image']) && isset($_FILES['video'])) {
            $data['imageUpload'] = $this->uploadImage();
            $data['videoUpload'] = $this->uploadVideo();
            return $data;
        }
        if (isset($_FILES['image'])) {
            return $this->uploadImage();
        }
        if (isset($_FILES['video'])) {
            return $this->uploadVideo();
        }
    }

    private function uploadImage()
    {
        $this->uploadDir = '../FileStorage/Images/';
        $uploadedFile = $_FILES['image']['tmp_name'];
        $fileSizeInBytes = $_FILES['image']['size'];

        // Check if the file size exceeds the maximum limit
        if ($fileSizeInBytes > $this->maxImageSize) {
            return ['message' => 'File size exceeds the maximum limit of ' . $this->maxImageSize . ' bytes'];
        }
        // Validate file type
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif']; // Add more image MIME types if needed
        try {
            $this->validateFileType($uploadedFile, $allowedImageTypes);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage()];
        }

        // Check if the uploaded file is an image
        $imageInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($imageInfo, $uploadedFile);
        finfo_close($imageInfo);

        if (strpos($mimeType, 'image/') !== 0) {
            return ['message' => 'Invalid image file format'];
        }

        $originalFileName = $_FILES['image']['name'];
        $uniqueFileName = time() . '_' . $originalFileName;
        $destination = $this->uploadDir . $uniqueFileName;

        // Move the uploaded image to the specified directory
        move_uploaded_file($uploadedFile, $destination);

        $url = str_replace('../', 'https://w20017074.nuwebspace.co.uk/', $destination);
        return $url ? ['message' => 'success', 'url' => $url] : ['message' => 'failed'];
    }

    private function uploadVideo()
    {
        $this->uploadDir = '../FileStorage/Videos/';
        $uploadedFile = $_FILES['video']['tmp_name'];
        $fileSizeInBytes = $_FILES['video']['size'];

        // Check if the file size exceeds the maximum limit
        if ($fileSizeInBytes > $this->maxVideoSize) {
            return ['message' => 'File size exceeds the maximum limit of ' . $this->maxVideoSize . ' bytes'];
        }

        // Check if the uploaded file is a video
        $videoInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($videoInfo, $uploadedFile);
        finfo_close($videoInfo);

        // $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/quicktime']; // Add more video MIME types if needed
        // if (!in_array($mimeType, $allowedVideoTypes)) {
        //     return ['message' => 'Invalid video file format'];
        // }
        // Validate file type
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif']; // Add more image MIME types if needed
        try {
            $this->validateFileType($uploadedFile, $allowedImageTypes);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage()];
        }

        $originalFileName = $_FILES['video']['name'];
        $uniqueFileName = time() . '_' . $originalFileName;
        $destination = $this->uploadDir . $uniqueFileName;

        // Move the uploaded video to the specified directory
        move_uploaded_file($uploadedFile, $destination);

        $url = str_replace('../', 'https://w20017074.nuwebspace.co.uk/', $destination);
        return $url ? ['message' => 'success', 'url' => $url] : ['message' => 'failed'];
    }

}
