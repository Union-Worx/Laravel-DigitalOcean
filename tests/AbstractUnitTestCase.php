<?php

declare(strict_types=1);

/*
 * This file is part of Laravel DigitalOcean.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Tests\DigitalOcean;

use GrahamCampbell\TestBenchCore\HelperTrait;
use GrahamCampbell\TestBenchCore\MockeryTrait;
use PHPUnit\Framework\TestCase;

/**
 * This is the abstract unit test case class.
 *
 * @author Graham Campbell <hello@gjcampbell.co.uk>
 */
abstract class AbstractUnitTestCase extends TestCase
{
    use HelperTrait;
    use MockeryTrait;
}
