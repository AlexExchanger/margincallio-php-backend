<?php

class GlobalRates
{
    public static function get($ticker)
    {

        $urls = [
            'https://btc-e.com/api/2/btc_usd/ticker' => ['USDBTC', 'btc-e'],
            'https://btc-e.com/api/2/btc_eur/ticker' => ['EURBTC', 'btc-e'],
            'https://btc-e.com/api/2/btc_rur/ticker' => ['RURBTC', 'btc-e'],
            'http://www.bitstamp.net/api/ticker/' => ['USDBTC', 'bitstamp'],
            'http://campbx.com/api/xticker.php' => ['USDBTC', 'campbx'],
            'https://api.kraken.com/0/public/Ticker?pair=XXBTZUSD' => ['USDBTC', 'kraken'],
            'https://api.kraken.com/0/public/Ticker?pair=XXBTZEUR' => ['EURBTC', 'kraken'],
            'https://api.bitfinex.com/v1/ticker/btcusd' => ['USDBTC', 'bitfinex'],
        ];

        $urls = array_filter($urls, function ($v) use ($ticker) {
            return $v[0] == $ticker;
        });

        $mh = curl_multi_init();
        $curls = [];
        foreach ($urls as $url => $data) {
            $curls[$url] = curl_init();
            curl_setopt_array($curls[$url], [
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36",
            ]);
            curl_multi_add_handle($mh, $curls[$url]);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $result = [];
        foreach ($urls as $url => $data) {
            $response = curl_multi_getcontent($curls[$url]);
            curl_multi_remove_handle($mh, $curls[$url]);
            $response = json_decode($response, true);
            if (!is_array($response)) {
                continue;
            }

            $values = [];

            switch ($data[1]) {
                case 'btc-e':
                    $values = [
                        'high' => (float)$response['ticker']['high'],
                        'low' => (float)$response['ticker']['low'],
                        'sell' => (float)$response['ticker']['sell'],
                        'buy' => (float)$response['ticker']['buy'],
                        'volume' => (float)$response['ticker']['vol_cur'],
                        'time' => (int)$response['ticker']['updated']
                    ];
                    break;
                case 'bitstamp':
                    $values = [
                        'high' => (float)$response['high'],
                        'low' => (float)$response['low'],
                        'sell' => (float)$response['ask'],
                        'buy' => (float)$response['bid'],
                        'volume' => (float)$response['volume'],
                        'time' => (int)$response['timestamp']
                    ];
                    break;
                case 'campbx':
                    $values = [
                        'high' => null,
                        'low' => null,
                        'sell' => (float)$response['Best Ask'],
                        'buy' => (float)$response['Best Bid'],
                        'volume' => null,
                        'time' => null
                    ];
                    break;
                case 'kraken':
                    $k = [
                        'USDBTC' => 'XXBTZUSD',
                        'EURBTC' => 'XXBTZEUR'
                    ];
                    $values = [
                        'high' => null,
                        'low' => null,
                        'sell' => (float)$response['result'][$k[$data[0]]]['a'][0],
                        'buy' => (float)$response['result'][$k[$data[0]]]['b'][0],
                        'volume' => null,
                        'time' => null
                    ];
                    break;
                case 'bitfinex':
                    $values = [
                        'high' => null,
                        'low' => null,
                        'sell' => (float)$response['ask'],
                        'buy' => (float)$response['bid'],
                        'volume' => null,
                        'time' => (int)$response['timestamp']
                    ];
                    break;
            }

            $result[] = [
                'ticker' => $data[0],
                'provider' => $data[1],
                'values' => $values,
            ];
        }

        curl_multi_close($mh);
        return $result;
    }
} 