<?php

namespace GeorgRinger\KesearchSuggest\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SuggestController extends \Tpwd\KeSearch\Plugins\PluginBase
{

    private int $configurationId = 0;

    public function main(string $content, array $conf, ServerRequestInterface $request): string
    {
        $this->configurationId = (int)($request->getQueryParams()['tx_kesearch_pi1']['configuration'] ?? 0);
        if ($this->configurationId === 0) {
            return json_encode([]);
        }

        // initializes plugin configuration
        // @extensionScannerIgnoreLine
        $this->init($request);
        if (!($this->piVars['sword'] ?? '')) {
            return json_encode([]);
        }
        $this->db->setPluginbase($this);

        $result = [
            'results' => $this->getResults(),
            'autocomplete' => $this->getAutocomplete(),
        ];

        return json_encode($result);
    }

    private function getResults(): array
    {
        $searchResults = $this->db->getSearchResults();

        $keysToKeep = ['uid', 'title', 'score', 'type', 'targetpid', 'orig_uid', 'params'];
        return array_map(static function ($item) use ($keysToKeep) {
            return array_intersect_key($item, array_flip($keysToKeep));
        }, $searchResults);
    }

    private function getAutocomplete(): array
    {
        $searchWord = trim($this->piVars['sword']);
        if (empty($searchWord)) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_kesearch_stat_search');
        $searchPlaceholderForLike = $queryBuilder->escapeLikeWildcards($searchWord) . '%';

        $rows = $queryBuilder
            ->select('searchphrase')
            ->from('tx_kesearch_stat_search')
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $this->cObj->data['pages']), ArrayParameterType::INTEGER)),
                $queryBuilder->expr()->like('searchphrase', $queryBuilder->createNamedParameter($searchPlaceholderForLike)),
                $queryBuilder->expr()->gt('hits', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->groupBy('searchphrase')
            ->orderBy('searchphrase')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($rows, 'searchphrase');
    }


    public function getFlexFormConfiguration(): array
    {
        $this->cObj->data = BackendUtility::getRecord('tt_content', $this->configurationId);
        $data = parent::getFlexFormConfiguration();
        $data['loadFlexformsFromOtherCE'] = $this->configurationId;

        return $data;
    }


}
