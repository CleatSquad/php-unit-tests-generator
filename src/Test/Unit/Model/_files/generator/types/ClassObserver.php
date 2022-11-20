<?php

namespace CleatSquad\PhpUnitTestGenerator\Model\Generator\Types;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ClassObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

    }
}
