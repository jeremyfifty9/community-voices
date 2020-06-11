<?php

namespace CommunityVoices\App\Website\Component;

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

        if ($debug) {
            var_dump($response);
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

        if ($request->files->has('file')) {
            $file = $request->files->get('file');

            if (is_array($file)) {
                foreach ($file as $index => $f) {
                    $data["file[{$index}]"] = new \CURLFile($f->getPathName(), $f->getMimeType());
                }
            } else {
                $data['file'] = new \CURLFile($file->getPathName(), $file->getMimeType());
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getenv('API_URL') . $path);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
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
