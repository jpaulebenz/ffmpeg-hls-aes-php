<?php
$hlsQualities = ['426x240', '640x360', '960x540'];
$keyFile = getcwd() . DIRECTORY_SEPARATOR. 'trans' . DIRECTORY_SEPARATOR . 'enc.keyinfo';
$keyGenValue = shell_exec("openssl rand 16");
$keyFilePath = getcwd() . DIRECTORY_SEPARATOR. 'trans' . DIRECTORY_SEPARATOR . 'enc.key';
$m3u8FilePath = getcwd() . DIRECTORY_SEPARATOR. 'trans' . DIRECTORY_SEPARATOR . 'playlist.m3u8';
$filePath = getcwd() . DIRECTORY_SEPARATOR. 'demovideo.mp4';
$destinationFolder = getcwd() . DIRECTORY_SEPARATOR. 'output';

// Preparation for conversion
$content = "enc.key\r\n" . $destinationFolder . "/enc.key\r\nad62700d4e74263579281a3762f8b724";
file_put_contents($keyFile, $content, LOCK_EX);
copy ( $keyFilePath, $destinationFolder. DIRECTORY_SEPARATOR. 'enc.key' );
$keyFilePath = $destinationFolder. DIRECTORY_SEPARATOR. 'enc.key';
file_put_contents($keyFilePath, $keyGenValue, LOCK_EX);
copy($m3u8FilePath, $destinationFolder . DIRECTORY_SEPARATOR.'playlist.m3u8');


// Initiate transcode of HLS with FFMPEG
$hlsParams = '';
foreach ($hlsQualities as $quality) {
        $hlsParams .= ' -ac 2 -threads 3 -profile:v main -s ' . $quality . ' -q:v 1 -hls_list_size 0 -strict -2 -hls_time 5 -hls_key_info_file ' . $keyFile . ' -hls_segment_filename "' . $destinationFolder. DIRECTORY_SEPARATOR. $quality . 'fileSequences%d.ts" ' . $destinationFolder. DIRECTORY_SEPARATOR. $quality . 'upload_prog_indexes.m3u8';
}
$comment = 'nice ffmpeg -i ' . $filePath . $hlsParams;
shell_exec("rm -rf " . $destinationFolder . "/*");
$output = shell_exec($comment . "  2>&1; echo $?");
$output = explode(PHP_EOL, $output);
var_dump($output);
//$changeUrlForKey();
if($output[count($output) - 2] === '0'){
    echo 'Conversion done';
    //after successful completion of the conversion
    $source = rtrim(trim($destinationFolder), "/");
    if ($handle = opendir($source)) {
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== ".." && $file !== ".DS_Store") {
                //S3 client to upload file
                $credentials = array(
                    'region' => '',
                    'version' => 'latest',
                    'credentials' => [
                        'key' => '',
                        'secret' => ''
                    ]
                );
                //S3 Client with importing SDK
                $s3Client = S3Client::factory($credentials);
                //S3 Bucket details
                $awsS3Bucket = "bucket name";
                //S3 Put object
                $s3Client->putObject(array(
                    'Bucket' => $awsS3Bucket,
                    'SourceFile' => $source . "/" . $file,
                    'Key' => 'demo-video /' . $file,
                    'ACL' => 'public-read',
                    'ServerSideEncryption' => 'AES256',
                ));
            }
        }
        closedir($handle);
    }
}else{
    shell_exec("rm -rf " . $destinationFolder . "/*");
}