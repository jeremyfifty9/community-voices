<?php

namespace CommunityVoices\App\Website\Component;

use CommunityVoices\App\Api\Component\Exception\AccessDenied;

class ApiProvider
{
    public function getJson($path, $request, $debug = false)
    {
        return json_decode($this->get($path, $request, $debug));
    }

    public function getQueriedJson($path, $request, $debug = false)
    {
        $query = http_build_query($request->query->all());

        return $this->getJson($path . ($query ? '?' . $query : ''), $request, $debug);
    }

    public function get($path, $request, $debug = false)
    {
        if (!empty($request->cookies->all())) {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Cookie: " . $_SERVER['HTTP_COOKIE']
                ]
            ];
            $context = stream_context_create($opts);
        }

        $response = file_get_contents(getenv('API_URL') . $path, false, $context ?? null);

        // Need to check if this is an access denied.
        // It seems the JSON responses have two '\\', while the HTML responses have one.
        if (
            strpos($response, 'CommunityVoices\App\Api\Component\Exception\AccessDenied') !== false ||
            strpos($response, 'CommunityVoices\\\App\\\Api\\\Component\\\Exception\\\AccessDenied') !== false
        ) {
            // Rather than passing the identity, we will just pass if the identity exists or not,
            // as that is all that AccessDenied needs.
            throw new AccessDenied(strpos($response, AccessDenied::LOGGED_IN_MESSAGE) !== false);
        }

        if ($debug) {
            echo $response;
            die();
        }

        return $response;
    }

    public function postJson($path, $request, $debug = false)
    {
        return json_decode($this->post($path, $request, $debug));
    }

    public function post($path, $request, $debug = false)
    {
        $data = $request->request->all();

        foreach ($request->files->all() as $key => $value) {
            $file = $request->files->get($key);

            if (is_array($file)) {
                foreach ($file as $index => $f) {
                    $data["{$key}[{$index}]"] = new \CURLFile($f->getPathName(), $f->getMimeType());
                }
            } else if (!is_null($file)) {
                $data[$key] = new \CURLFile($file->getPathName(), $file->getMimeType());
            }
        }

        // If we have an array in our POST data, we need to convert it into a format
        // that will not result in an array to string conversion error from PHP.
        // But, we also need to not call `http_build_query` on the array,
        // as that breaks file upload.
        // So, this is a custom processor that turns every array into its component
        // parts without forcing us to use `http_build_query`.
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $index => $entry) {
                    $data[$key . '[' . $index . ']'] = $entry;
                }

                unset($data[$key]);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getenv('API_URL') . $path);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: " . $_SERVER['HTTP_COOKIE']]);

        if (!$debug)
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($debug) {
            die();
        }

        return $result;
    }
}
