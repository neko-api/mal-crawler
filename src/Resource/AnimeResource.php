<?php

namespace NekoAPI\Component\MalCrawler\Resource;

use NekoAPI\Component\Entity\AlternativeTitles;
use NekoAPI\Component\Entity\Anime;
use NekoAPI\Component\Entity\Episode;
use NekoAPI\Component\Entity\Genre;
use NekoAPI\Component\Entity\Information;
use NekoAPI\Component\Entity\Producer;
use NekoAPI\Component\Entity\Score;
use NekoAPI\Component\Entity\Season;
use NekoAPI\Component\Entity\Source;
use NekoAPI\Component\Entity\Statistics;
use NekoAPI\Component\Entity\Status;
use NekoAPI\Component\Entity\Type;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AnimeResource
 *
 * @package NekoAPI\Component\MalCrawler\Resource
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class AnimeResource extends BaseResource
{
    public function fetch(string $url)
    {
        $crawler = $this->loadURL($url);

        [$id, $slug] = $this->extractIdSlug($url);

        $title = $this->extractXpathNodeText($crawler, '//*[@id="contentWrapper"]/div[1]/h1/span');

        $anime = new Anime($id, $slug, $title);

        /**
         *  Image
         */
        $image = $this->extractXpathNodeAttribute(
            $crawler,
            '//*[@id="content"]/table/tr/td[1]/div/div[1]/a/img',
            'src'
        );

        $anime->setImage($image);

        /**
         *  URL
         */
        $anime->setUrl($url);

        $attributes = $this->extractAttributes($crawler);

        /**
         *  Synopsis
         */
        $anime->setSynopsis(
            $this->extractXpathNodeText(
                $crawler,
                '//*[@id="content"]/table/tr/td[2]/div/table/tr[1]/td/span'
            )
        );

        $this->parseAttributes($attributes, $anime);

        $episodeCrawler = $this->loadURL($url . '/episode');

        $episodes = $episodeCrawler->filter('.ascend > tr.episode-list-data')->each(function (Crawler $node) {
            $nr    = $node->filter('td:nth-child(1)')->text();
            $name  = $node->filter('td:nth-child(3)')->text();

            [$name] = explode("\n", $name);

            $aired = $node->filter('td:nth-child(4)')->text();

            if (null !== $aired && 'N/A' !== $aired) {
                $aired = new \DateTime($aired);
            }

            return new Episode((int) $nr, $name, $aired);
        });

        $anime->setEpisodes($episodes);

        return $anime;
    }

    private function extractIdSlug(string $url)
    {
        if (false === preg_match('/anime\/(\d+)\/(.*)$/', $url, $matches)) {

        }

        return [$matches[1], $matches[2]];
    }

    private function extractAttributes(Crawler $crawler): array
    {
        $result = [];

        $crawler->filterXPath('//*[@id="content"]/table/tr/td[1]/div/div')->each(
            function (Crawler $node) use (&$result) {
                $text = $node->text();

                $titleNode = $node->filter('span');
                if ($titleNode->count() < 1) {
                    return;
                }

                $title = $titleNode->first()->text();

                $result[strtolower($title)] = $node;
            }
        );

        return $result;
    }

    private function parseAttributes(array $attributes, Anime $anime)
    {
        $attributeCallbacks = [
            'english:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $titles = $anime->getAlternativeTitles() ?? new AlternativeTitles(null, null);
                $titles->setEnglish(rtrim($title));
                $anime->setAlternativeTitles($titles);
            },
            'japanese:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $titles = $anime->getAlternativeTitles() ?? new AlternativeTitles(null, null);
                $titles->setJapanese(rtrim($title));
                $anime->setAlternativeTitles($titles);
            },
            'synonyms:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $titles = $anime->getAlternativeTitles() ?? new AlternativeTitles(null, null);
                $titles->setSynonyms(explode(', ', rtrim($title)));
                $anime->setAlternativeTitles($titles);
            },
            'type:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $type = new Type(rtrim($title));

                $information = $anime->getInformation() ?? new Information();
                $information->setType($type);

                $anime->setInformation($information);
            },
            'episodes:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $information = $anime->getInformation() ?? new Information();

                $value = (int) rtrim($title);
                if ($value !== 0) {
                    $information->setEpisodes($value);
                }

                $anime->setInformation($information);
            },
            'status:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $status = new Status(rtrim($title));

                $information = $anime->getInformation() ?? new Information();
                $information->setStatus($status);

                $anime->setInformation($information);
            },
            'aired:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ($value === 'Not available') {
                    return;
                }

                if (preg_match('/\sto\s/', $value)) {
                    list($from, $to) = explode(' to ', $value);

                    $from = new \DateTime($from);

                    if ($to === '?') {
                        $to = null;
                    } else {
                        $to = new \DateTime($to);
                    }
                } else {
                    $from = new \DateTime($value);
                    $to = null;
                }

                $information = $anime->getInformation() ?? new Information();

                if (null !== $from) {
                    $information->setAiredFrom($from);
                }

                if (null !== $to) {
                    $information->setAiredTo($to);
                }

                $anime->setInformation($information);
            },
            'premiered:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ('?' === $value) {
                    return;
                }

                [$name, $year] = explode(' ', $value, 2);

                $information = $anime->getInformation() ?? new Information();

                $information->setPremiered(new Season($name, $year));

                $anime->setInformation($information);
            },
            'broadcast:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ('?' === $value) {
                    return;
                }

                $information = $anime->getInformation() ?? new Information();

                $information->setBroadcast($value);

                $anime->setInformation($information);
            },
            'producers:' => function (Crawler $node, Anime $anime) {
                $information = $anime->getInformation() ?? new Information();

                $node->filter('a')->each(function (Crawler $node) use ($information) {
                    $information->addProducer(
                        new Producer(
                            static::extractIdFromUrl('producer', $node->attr('href')),
                            $node->text()
                        )
                    );
                });

                $anime->setInformation($information);
            },
            'licensors:' => function (Crawler $node, Anime $anime) {
                $information = $anime->getInformation() ?? new Information();

                $node->filter('a')->each(function (Crawler $node) use ($information) {
                    $information->addLicensee(
                        new Producer(
                            static::extractIdFromUrl('producer', $node->attr('href')),
                            $node->text()
                        )
                    );
                });

                $anime->setInformation($information);
            },
            'studios:' => function (Crawler $node, Anime $anime) {
                $information = $anime->getInformation() ?? new Information();

                $node->filter('a')->each(function (Crawler $node) use ($information) {
                    $information->addStudio(
                        new Producer(
                            static::extractIdFromUrl('producer', $node->attr('href')),
                            $node->text()
                        )
                    );
                });

                $anime->setInformation($information);
            },
            'source:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ('?' === $value) {
                    return;
                }

                $information = $anime->getInformation() ?? new Information();

                $information->setSource(new Source($value));

                $anime->setInformation($information);
            },
            'genres:' => function (Crawler $node, Anime $anime) {
                $information = $anime->getInformation() ?? new Information();

                $node->filter('a')->each(function (Crawler $node) use ($information) {
                    $information->addGenre(
                        new Genre(
                            static::extractIdFromUrl('genre', $node->attr('href')),
                            $node->text()
                        )
                    );
                });

                $anime->setInformation($information);
            },
            'duration:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ('?' === $value) {
                    return;
                }

                $information = $anime->getInformation() ?? new Information();

                $information->setDuration($value);

                $anime->setInformation($information);
            },
            'rating:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                if ('?' === $value) {
                    return;
                }

                $information = $anime->getInformation() ?? new Information();

                $information->setRating($value);

                $anime->setInformation($information);
            },
            'score:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                [$score] = explode(' ', $value, 1);

                $statistics = $anime->getStatistics() ?? new Statistics();

                if (preg_match('/\(scored\sby\s([\d,]+)\susers\)/', $value, $matches)) {
                    $statistics->setScore(
                        new Score(
                            (double) $score,
                            (int) str_replace(',', '', $matches[1])
                        )
                    );
                } else {
                    $statistics->setScore(new Score((double) $score, 0));
                }

                $anime->setStatistics($statistics);
            },
            'ranked:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                [$score] = explode(' ', $value, 1);

                $statistics = $anime->getStatistics() ?? new Statistics();

                $statistics->setRanked(
                    (int) substr($score, 1)
                );

                $anime->setStatistics($statistics);
            },
            'popularity:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                $statistics = $anime->getStatistics() ?? new Statistics();

                $statistics->setPopularity(
                    (int) substr($value, 1)
                );

                $anime->setStatistics($statistics);
            },
            'members:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                $statistics = $anime->getStatistics() ?? new Statistics();

                $statistics->setMembers(
                    (int) str_replace(',', '', $value)
                );

                $anime->setStatistics($statistics);
            },
            'favorites:' => function (Crawler $node, Anime $anime) {
                [, $title] = preg_split('/:\s*/', $node->text());

                $value = rtrim($title);

                $statistics = $anime->getStatistics() ?? new Statistics();

                $statistics->setFavorites(
                    (int) str_replace(',', '', $value)
                );

                $anime->setStatistics($statistics);
            },
        ];

        foreach ($attributes as $attribute => $node) {
            if (isset($attributeCallbacks[$attribute])) {
                call_user_func($attributeCallbacks[$attribute], $node, $anime);
            }
        }
    }

    private static function extractIdFromUrl(string $type, string $url)
    {
        if (preg_match('/^\/anime\/' . $type . '\/(\d+)\//', $url, $matches)) {
            return $matches[1];
        }
    }
}