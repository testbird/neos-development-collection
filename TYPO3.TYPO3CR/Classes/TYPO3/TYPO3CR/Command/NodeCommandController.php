<?php
namespace TYPO3\TYPO3CR\Command;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Cli\DescriptionAwareCommandControllerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Node command controller for the TYPO3.TYPO3CR package
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends CommandController implements DescriptionAwareCommandControllerInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $pluginConfigurations = array();

    /**
     * Repair inconsistent nodes
     *
     * This command analyzes and repairs the node tree structure and individual nodes
     * based on the current node type configuration.
     *
     * The following checks will be performed:
     *
     * {pluginDescriptions}
     * <b>Examples:</b>
     *
     * ./flow node:repair
     *
     * ./flow node:repair --node-type TYPO3.Neos.NodeTypes:Page
     *
     * @param string $nodeType Node type name, if empty update all declared node types
     * @param string $workspace Workspace name, default is 'live'
     * @param boolean $dryRun Don't do anything, but report actions
     * @param boolean $cleanup If FALSE, cleanup tasks are skipped
     * @return void
     */
    public function repairCommand($nodeType = null, $workspace = 'live', $dryRun = false, $cleanup = true)
    {
        $this->pluginConfigurations = self::detectPlugins($this->objectManager);

        if ($this->workspaceRepository->countByName($workspace) === 0) {
            $this->outputLine('Workspace "%s" does not exist', array($workspace));
            exit(1);
        }

        if ($nodeType !== null) {
            if ($this->nodeTypeManager->hasNodeType($nodeType)) {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeType);
            } else {
                $this->outputLine('Node type "%s" does not exist', array($nodeType));
                exit(1);
            }
        }

        if ($dryRun) {
            $this->outputLine('Dry run, not committing any changes.');
        }

        foreach ($this->pluginConfigurations as $pluginConfiguration) {
            /** @var NodeCommandControllerPluginInterface $plugin */
            $plugin = $pluginConfiguration['object'];
            $this->outputLine('<b>' . $plugin->getSubCommandShortDescription('repair') . '</b>');
            $this->outputLine();
            $plugin->invokeSubCommand('repair', $this->output, $nodeType, $workspace, $dryRun, $cleanup);
            $this->outputLine();
        }

        $this->outputLine('Node repair finished.');
    }

    /**
     * Create missing child nodes
     *
     * This is a legacy command which automatically creates missing child nodes for a
     * node type based on the structure defined in the NodeTypes configuration.
     *
     * NOTE: Usage of this command is deprecated and it will be remove eventually.
     *       Please use node:repair instead.
     *
     * @param string $nodeType Node type name, if empty update all declared node types
     * @param string $workspace Workspace name, default is 'live'
     * @param boolean $dryRun Don't do anything, but report missing child nodes
     * @return void
     * @see typo3.typo3cr:node:repair
     * @deprecated since 1.2
     */
    public function autoCreateChildNodesCommand($nodeType = null, $workspace = 'live', $dryRun = false)
    {
        $this->pluginConfigurations = self::detectPlugins($this->objectManager);
        $this->pluginConfigurations['TYPO3\TYPO3CR\Command\NodeCommandControllerPlugin']['object']->invokeSubCommand('repair', $this->output, $nodeType, $workspace, $dryRun);
    }

    /**
     * Processes the given short description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $shortDescription The short command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified short command description
     */
    public static function processShortDescription($controllerCommandName, $shortDescription, ObjectManagerInterface $objectManager)
    {
        return $shortDescription;
    }

    /**
     * Processes the given description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $description The command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified command description
     */
    public static function processDescription($controllerCommandName, $description, ObjectManagerInterface $objectManager)
    {
        $pluginConfigurations = self::detectPlugins($objectManager);
        $pluginDescriptions = '';
        foreach ($pluginConfigurations as $className => $configuration) {
            $pluginDescriptions .= $className::getSubCommandDescription($controllerCommandName) . PHP_EOL;
        }
        return str_replace('{pluginDescriptions}', $pluginDescriptions, $description);
    }

    /**
     * Detects plugins for this command controller
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     */
    protected static function detectPlugins(ObjectManagerInterface $objectManager)
    {
        $pluginConfigurations = array();
        $classNames = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService')->getAllImplementationClassNamesForInterface('TYPO3\TYPO3CR\Command\NodeCommandControllerPluginInterface');
        foreach ($classNames as $className) {
            $pluginConfigurations[$className] = array(
                'object' => $objectManager->get($objectManager->getObjectNameByClassName($className))
            );
        }
        return $pluginConfigurations;
    }
}
