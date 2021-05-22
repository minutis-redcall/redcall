# pagination-bundle

This bundle provides helpers for Pagerfanta with Bootstrap 4, in order to add several paginations in a single page
quickly.

## Demo

This demo shows the code required to render, in the same page, ongoing and finished emergency campaigns for first
aiders, or in your case ad campaigns for a marketing team.

### Controller

```php
<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Manager\CampaignManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @param PaginationManager $paginationManager
     * @param CampaignManager   $campaignManager
     */
    public function __construct(PaginationManager $paginationManager, CampaignManager $campaignManager)
    {
        $this->paginationManager = $paginationManager;
        $this->campaignManager   = $campaignManager;
    }

    public function index(): Response
    {
        $ongoing  = $this->campaignManager->getActiveCampaignsQueryBuilder();
        $finished = $this->campaignManager->getInactiveCampaignsQueryBuilder();

        return $this->render('campaign/index.html.twig', [
            'data' => [
                'ongoing'  => $this->paginationManager->getPager($ongoing, 'ongoing'),
                'finished' => $this->paginationManager->getPager($finished, 'finished'),
            ],          
        ]);
    }
}
```

### View

```twig
{% import '@Pagination/macros.html.twig' as macros %}

{% for prefix, pager in data %}

    <h3>{{ prefix|titlle }} campaigns</h3>

    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Started at</th>
                ...
            </tr>
        </thead>
        <tbody>
            {% for campaign in pager.currentPageResults %}
                <tr>
                    <td>{{ campaign.title }}</td>
                    <td>{{ campaign.startedAt|date('d/m/Y H:i') }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>

    {{ macros.pager(pager, prefix) }}

    <hr/>

{% endfor %}
```