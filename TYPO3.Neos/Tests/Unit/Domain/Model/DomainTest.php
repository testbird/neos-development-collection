<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "Domain" domain model
 *
 */
class DomainTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function setHostPatternAllowsForSettingTheHostPatternOfTheDomain() {
		$domain = new \TYPO3\TYPO3\Domain\Model\Domain();
		$domain->setHostPattern('typo3.com');
		$this->assertSame('typo3.com', $domain->getHostPattern());
	}

	/**
	 * @test
	 */
	public function setSiteSetsTheSiteTheDomainIsPointingTo() {
		$mockSite = $this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE);

		$domain = new \TYPO3\TYPO3\Domain\Model\Domain;
		$domain->setSite($mockSite);
		$this->assertSame($mockSite, $domain->getSite());
	}
}

?>