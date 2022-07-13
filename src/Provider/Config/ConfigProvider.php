<?php

declare(strict_types=1);

namespace Yurun\Nacos\Provider\Config;

use Yurun\Nacos\Provider\BaseProvider;
use Yurun\Nacos\Provider\Config\Model\HistoryListResponse;
use Yurun\Nacos\Provider\Config\Model\HistoryResponse;
use Yurun\Nacos\Provider\Config\Model\ListenerConfig;
use Yurun\Nacos\Provider\Config\Model\ListenerRequest;
use Yurun\Nacos\Provider\Config\Model\ListenerResponseItem;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\RequestMethod;

class ConfigProvider extends BaseProvider
{
    public const CONFIG_API_APTH = 'nacos/v1/cs/configs';

    public const CONFIG_HISTORY_API_APTH = 'nacos/v1/cs/history';

    protected ?ConfigListener $configListener = null;

    public function get(string $dataId, string $group, string $tenant = ''): string
    {
        return $this->client->request(self::CONFIG_API_APTH, [
            'dataId' => $dataId,
            'group'  => $group,
            'tenant' => $tenant,
        ])->body();
    }

    public function set(string $dataId, string $group, string $content, string $tenant = '', string $type = ''): bool
    {
        return 'true' === $this->client->request(self::CONFIG_API_APTH, [
            'dataId'  => $dataId,
            'group'   => $group,
            'content' => $content,
            'tenant'  => $tenant,
            'type'    => $type,
        ], RequestMethod::POST)->body();
    }

    public function delete(string $dataId, string $group, string $tenant = ''): bool
    {
        return 'true' === $this->client->request(self::CONFIG_API_APTH, [
            'dataId'  => $dataId,
            'group'   => $group,
            'tenant'  => $tenant,
        ], RequestMethod::DELETE)->body();
    }

    /**
     * @return ListenerResponseItem[]
     */
    public function listen(ListenerRequest $request, int $longPullingTimeout = 30000): array
    {
        $response = $this->client->request('nacos/v1/cs/configs/listener', $request->getRequestBody(), RequestMethod::POST, ['Long-Pulling-Timeout' => (string) $longPullingTimeout]);
        $result = [];
        foreach (explode('%01', trim($response->body())) as $item) {
            if ('' === $item) {
                continue;
            }
            $result[] = ListenerResponseItem::createFromListener($item);
        }

        return $result;
    }

    public function historyList(string $dataId, string $group, string $tenant = '', int $pageNo = 1, int $pageSize = 100): HistoryListResponse
    {
        return $this->client->request(self::CONFIG_HISTORY_API_APTH, [
            'search'   => 'accurate',
            'dataId'   => $dataId,
            'group'    => $group,
            'tenant'   => $tenant,
            'pageNo'   => $pageNo,
            'pageSize' => $pageSize,
        ], RequestMethod::GET, [], HistoryListResponse::class);
    }

    /**
     * @param string|int $nid
     */
    public function history($nid, string $dataId, string $group, string $tenant = ''): HistoryResponse
    {
        return $this->client->request(self::CONFIG_HISTORY_API_APTH, [
            'nid'    => $nid,
            'dataId' => $dataId,
            'group'  => $group,
            'tenant' => $tenant,
        ], RequestMethod::GET, [], HistoryResponse::class);
    }

    public function getConfigListener(ListenerConfig $listenerConfig): ConfigListener
    {
        return $this->configListener ??= new ConfigListener($this->getClient(), $listenerConfig);
    }
}
