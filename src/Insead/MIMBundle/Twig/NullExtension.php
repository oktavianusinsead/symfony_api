<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 4/12/16
 * Time: 4:56 PM
 */
namespace Insead\MIMBundle\Twig;

class NullExtension implements \Twig_ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {}

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'null_extension';
    }
}
