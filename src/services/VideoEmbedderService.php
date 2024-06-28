<?php
/**
 * Video Embedder plugin for Craft CMS 3.x
 *
 * Craft plugin to generate an embed URL from a YouTube or Vimeo URL.
 *
 * @link      http://github.com/mikestecker
 * @copyright Copyright (c) 2017 Mike Stecker
 */

namespace mikestecker\videoembedder\services;

use Embed\Exceptions\InvalidUrlException;
use mikestecker\videoembedder\VideoEmbedder;

use Craft;
use craft\base\Component;
use craft\helpers\Html;

use DOMDocument;
use Embed\Embed;

/**
 * @author    Mike Stecker
 * @package   VideoEmbedder
 * @since     1.0.0
 */
class VideoEmbedderService extends Component
{


    /**
     * Tap the Embed library
     * @param string $url
     */
    public function getInfo($url): void
    {
		// use oembed if vimeo
		/*if ($this->isVimeo($url) && !$this->isOembed($url)) {
			$url = 'https://vimeo.com/api/oembed.json?url='.$url;
		}

		$response = Embed::create($url, [
            'choose_bigger_image' => true,
            'parameters' => [],
		]);

		if ($this->isVimeo($url) && $this->isOembed($url)) {
			$data = json_decode($response->response->getContent());
			$data->aspectRatio = $data->width / $data->height;
			$data->code = $data->html;
			return $data;
		}

		return $response;*/
    }


    /**
     * Determine whether the url is a youtube or vimeo url
     * @param string $url
     */
    public function isVideo($url): bool
    {
		return $this->isYouTube($url) || $this->isVimeo($url);
    }

    /**
     * Is the url a youtube url
     * @param string $url
     */
    public function isYouTube($url): bool
    {
        return (str_contains($url, 'youtube.com/') || str_contains($url, 'youtu.be/'));
    }

    /**
     * Is the url a vimeo url
     * @param string $url
     */
    public function isVimeo($url): bool
    {
        return str_contains($url, 'vimeo.com/');
    }

    /**
     * Is the url a wistia url
     * @param string $url
     */
    public function isWistia($url): bool
    {
        return str_contains($url, 'wistia.com/');
    }

    /**
     * Is the url a Viddler url
     * @param string $url
     */
    public function isViddler($url): bool
    {
        return str_contains($url, 'viddler.com/');
	}
	
	/**
     * Is the url using oEmbed format
     * @param string $url
     */
    public function isOembed($url): bool
    {
        return str_contains($url, 'oembed.json?');
	}

    /**
     * Parse the YouTube URL, return the video ID
     * @param string $url
     * @return string
     */
    public function getYouTubeId($url)
    {
		preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? null;
    }

    /**
     * Parse the Vimeo URL, return the video ID
     * @param string $url
     * @return string
     */
    public function getVimeoId($url)
    {
        preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $matches);
        return $matches[3] ?? null;
    }


	public function getParams($params): string
	{
		$parameters = '?';
		$i = 0;
		foreach ($params as $k=>$v) {
			if (($parameters !== null) && ($i !== 0)) {
				$parameters .= '&';
			}

			$parameters .= sprintf('%s=%s', $k, $v);
			++$i;
		}

		return $parameters;
	}

    /**
     * Take a url and return the embed code
     *
     * @param string $url
     */
    public function embed( $url, $params = [], array $attributes = []) : string
    {
		$src = $this->getEmbedUrl($url, $params);
        $attributes['src'] = $src;
		return Html::tag('iframe','',$attributes);

		/*try {
            //$code = $this->getInfo($url)->code;

            // check if theree are any parameters passed along
            if (!empty($params)) {

                // looks like there are, now let's only do this for YouTube and Vimeo
                if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
                {

                    // for videos add autoplay check if the embed gets the code

                    // create an easy html parser to verify the iframe and add autoplay for video types
                    $dom = new DOMDocument;

                    // set error level so that the html parser doesn't raise exceptions on warnings
                    $internalErrors = libxml_use_internal_errors(true);

                    $dom->loadHTML($code);

                    // Restore error level
                    libxml_use_internal_errors($internalErrors);

                    // get the iframe
                    $frame = $dom->getElementsByTagName('iframe')->item(0);

                    //get the src from iframe
                    $src = $frame->getAttribute('src');


                    // check if url has any other parameters to properly add the parameter
                    if (strpos($src,'?') !== false) {
                        $src .= "&";
                    } else {
                        $src .= "?";
                    }

                    $parameters = '';
                    foreach ($params as $k=>$v) {
                        if ($parameters !== null) {
                            $parameters .= '&';
                        }
                        $parameters .= "{$k}={$v}";
                    }

                    $src .= $parameters;

                    // set the new src with all the parameters
                    $frame->setAttribute('src', $src);

                    // replace old iframe html with new one
                    return htmlspecialchars_decode($dom->saveHTML($frame));
                }
                else
                {
                    if (!empty($code)) {
                        // Not YouTube or Vimeo, just output the code
                        return $code;
                    }
                    else
                    {
                        return '';
                    }
                }
            }
            else
            {
                if (!empty($code)) {
                    // No parameters passed, just output the code
                    return $code;
                }
                else
                {
                    return '';
                }
            }
        } catch (InvalidUrlException $e)
        {
            // If the URL is invalid (because it's 404ing out or whatever) just return an empty string.
            return '';
        }*/

    }



    /**
     * Take a url and return the embed url
     *
     * @param string $url
     * @return string
     */
    public function getEmbedUrl($url, $params = [] )
    {

		$parameters = $this->getParams($params);

		if ($this->isYouTube($url)) {
			$id = $this->getYouTubeId($url);

			return '//www.youtube.com/embed/' . $id . $parameters;
		}

		if ($this->isVimeo($url)) {
			$id = $this->getVimeoId($url);

			return '//player.vimeo.com/video/' . $id . $parameters;
		}

        /*try {

            // looks like there are, now let's only do this for YouTube and Vimeo
            if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
            {
                $parameters = '';

                // check if theree are any parameters passed along
                if (!empty($params)) {

                    $parameters .= '?';
                    $i = 0;
                    foreach ($params as $k=>$v) {
                        if (($parameters !== null) && ($i !== 0)) {
                            $parameters .= '&';
                        }
                        $parameters .= "{$k}={$v}";
                        $i++;
                    }
                }

                if ($this->isYouTube($url)) {
                    $id = $this->getYouTubeId($url);

                    $embedUrl = '//www.youtube.com/embed/' . $id . $parameters;
                    return $embedUrl;
                } else if ($this->isVimeo($url)) {
                    $id = $this->getVimeoId($url);

                    $embedUrl = '//player.vimeo.com/video/' . $id . $parameters;
                    return $embedUrl;
                }
            }
            else
            {
                // return empty string
                return '';
            }

        } catch (InvalidUrlException $e)
        {
            // If the URL is invalid (because it's 404ing out or whatever) just return an empty string.
            return '';
        }*/

    }



    /**
     * Take a url and returns only the video ID
     *
     * @param string $url
     * @return string
     */
    public function getVideoId($url)
    {

		if ($this->isYouTube($url)) {
			return $this->getYouTubeId($url);
		}

		if ($this->isVimeo($url)) {
			return $this->getVimeoId($url);
		}

		return '';

        /*// looks like there are, now let's only do this for YouTube and Vimeo
        if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
        {
            if ($this->isYouTube($url))
            {
                return $this->getYouTubeId($url);
            }
            else if ($this->isVimeo($url))
            {
                return $this->getVimeoId($url);
            }
        }
        else
        {
            // return empty string
            return '';
        }*/
    }


    /**
     * Retrieves the thumbnail from a youtube or vimeo video
     * @param - $url
     * @return - string
     * 
    **/
    public function getVideoThumbnail($url): string {

		if ($this->isYouTube($url)) {
			$id = $this->getYouTubeId($url);
			return sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $id);
		}

		if ($this->isVimeo($url)) {
			$id = $this->getVimeoId($url);
			return "https://i.vimeocdn.com/video/".$id."_480x360.webp";
		}
		

		/*// check for vimeo, I don't like the way Embed returns the Vimeo thumbnail
        if($this->getInfo($url)->type == 'video' && $this->isVimeo($url))
        {
            $id = $this->getVimeoId($url);
            
            $data = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
            $data = json_decode($data);
            
            $image = $this->cleanUrl($data[0]->thumbnail_large);
            
            return $image;
        }
        else
        {
            // not vimeo, use Embed
            $image = $this->cleanUrl($this->getInfo($url)->image);
            
            // Check if anything exists
            if (!empty($image)) {
                return $image;
            }
            else
            {
                return '';
            }
		}*/
		
		return '';
    }

    public function getProviderName($url): ?string
    {
        if ($this->isVideo($url)) {
            if ($this->isYouTube($url)) {
                return 'youtube';
            } elseif ($this->isVimeo($url)) {
                return 'vimeo';
            }
        }

        return null;
    }

    // private function cleanUrl($url): string|array|null {
    //     return preg_replace( '/^https?:/', '', $url );
    // }    
}
