<?php
namespace Wwwision\Neos\AssetNodeType;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeServiceInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Custom Node Implementation for the "Wwwision.Neos.AssetNodeType:AssetList" node type
 *
 * Hooks into set/getProperty for the "assets" property redirecting it to ""Wwwision.Neos.AssetNodeType:Asset" child nodes
 */
class AssetListNode extends Node
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;


    /**
     * Intercepts the retrieval of the "assets" property and returns assets from "Wwwision.Neos.AssetNodeType:Asset" child nodes instead
     *
     * @inheritdoc
     */
    public function getProperty($propertyName, $returnNodesAsIdentifiers = false)
    {
        if ($propertyName === 'assets') {
            return $this->getAssetsFromAssetChildNodes();
        }
        return parent::getProperty($propertyName, $returnNodesAsIdentifiers);
    }

    /**
     * Intercepts mutations of the "assets" property and creates "Wwwision.Neos.AssetNodeType:Asset" child nodes instead
     *
     * @inheritdoc
     */
    public function setProperty($propertyName, $value)
    {
        if ($propertyName === 'assets') {
            if (!$this->isNodeDataMatchingContext()) {
                $this->materializeNodeData();
            }
            $this->createAssetChildNodes($value);

            // don't set the "assets" property in the AssetList NodeData to avoid duplication & confusion
            $value = null;
        }
        return parent::setProperty($propertyName, $value);
    }

    /**
     * @return Asset[]
     */
    protected function getAssetsFromAssetChildNodes()
    {
        $assetsNode = $this->getNode('assetNodes');
        if ($assetsNode === null) {
            return [];
        }
        /** @var NodeInterface[] $assetNodes */
        $assetNodes = $assetsNode->getChildNodes('Wwwision.Neos.AssetNodeType:Asset');
        $assets = [];
        foreach ($assetNodes as $assetNode) {
            $asset = $assetNode->getProperty('asset');
            if ($asset instanceof Asset) {
                $assets[] = $asset;
            }
        }
        return $assets;
    }

    /**
     * @param Asset[] $assets
     * @return void
     */
    protected function createAssetChildNodes(array $assets)
    {
        $assetsNode = $this->getNode('assetNodes');
        if ($assetsNode === null) {
            $assetsNode = $this->createNode('assetNodes', $this->nodeTypeManager->getNodeType('TYPO3.Neos:ContentCollection'));
        }

        $existingAssetNodes = $this->getAssetChildNodes($assetsNode);
        $assetNode = null;

        // create asset node for each new asset
        foreach ($assets as $asset) {
            $assetId = $this->persistenceManager->getIdentifierByObject($asset);
            if (isset($existingAssetNodes[$assetId])) {
                if (isset($assetNode)) {
                    // ensure the correct order
                    $existingAssetNodes[$assetId]->moveAfter($assetNode);
                }
                $assetNode = $existingAssetNodes[$assetId];
                unset($existingAssetNodes[$assetId]);
                continue;
            }
            $assetNodeType = $this->nodeTypeManager->getNodeType('Wwwision.Neos.AssetNodeType:Asset');
            $assetNode = $assetsNode->createNode($this->nodeService->generateUniqueNodeName($assetsNode->getPath()), $assetNodeType);
            $assetNode->setProperty('asset', $asset);
        }

        // remove remaining asset nodes
        foreach ($existingAssetNodes as $remainingAssetNode) {
            $remainingAssetNode->remove();
        }
    }

    /**
     * @param NodeInterface $assetsNode
     * @return NodeInterface[]
     */
    protected function getAssetChildNodes(NodeInterface $assetsNode)
    {
        /** @var NodeInterface[] $assetNodes */
        $assetNodes = $assetsNode->getChildNodes('Wwwision.Neos.AssetNodeType:Asset');
        $assetNodesByAssetIdentifier = [];
        foreach ($assetNodes as $assetNode) {
            $assetNodesByAssetIdentifier[$this->persistenceManager->getIdentifierByObject($assetNode->getProperty('asset'))] = $assetNode;
        }
        return $assetNodesByAssetIdentifier;
    }
}