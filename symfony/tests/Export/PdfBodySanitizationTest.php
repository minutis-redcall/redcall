<?php

namespace App\Tests\Export;

use App\Entity\Communication;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression (CERT-FR RM #1080870, ANSSI-06): the PDF export hands the
 * email body to mPDF, which fetches every resource it references (remote
 * URLs on the internal network / metadata server, or local file paths).
 */
class PdfBodySanitizationTest extends KernelTestCase
{
    public function testEmailBodyResourcesNeverReachMpdf(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $fixtures  = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );

        $campaign      = $fixtures->createCampaign('PDF SSRF');
        $communication = $fixtures->createCommunication(
            $campaign,
            Communication::TYPE_EMAIL,
            '<p>Hello</p>'
            .'<img src="http://169.254.169.254/computeMetadata/v1/token">'
            .'<img src="/etc/passwd">'
            .'<div style="background-image:url(http://10.0.0.1/internal)">x</div>'
        );

        $html = $container->get('twig')->render('export/pdf/body.html.twig', [
            'current_date'  => new \DateTime(),
            'campaign'      => $campaign,
            'communication' => $communication,
            'tables'        => [],
        ]);

        $this->assertStringContainsString('Hello', $html);
        $this->assertStringNotContainsString('169.254.169.254', $html);
        $this->assertStringNotContainsString('/etc/passwd', $html);
        $this->assertStringNotContainsString('10.0.0.1', $html);
    }
}
