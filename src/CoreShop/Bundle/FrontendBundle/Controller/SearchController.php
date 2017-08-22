<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\FrontendBundle\Controller;

use CoreShop\Bundle\FrontendBundle\Form\Type\SearchType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\Paginator\Paginator;

class SearchController extends FrontendController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function widgetAction(Request $request): Response
    {
        $form = $this->createSearchForm();

        return $this->renderTemplate('CoreShopFrontendBundle:Search:_widget.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function searchAction(Request $request): Response
    {
        $form = $this->createSearchForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $formData = $form->getData();
            $text = $formData['text'];
            $page = $request->get('page', 1);
            $itemsPerPage = 10;

            $query = [
                'active = 1',
                'name LIKE ?',
                'description LIKE ?',
                'shortDescription LIKE ?',
                'sku LIKE ?'
            ];
            $queryParams = [
                '%' . $text . '%',
                '%' . $text . '%',
                '%' . $text . '%',
                '%' . $text . '%'
            ];

            $list = $this->get('coreshop.repository.product')->getList();
            $list->setCondition(implode(' OR ', $query), $queryParams);

            $paginator = new Paginator($list);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage($itemsPerPage);

            return $this->renderTemplate('CoreShopFrontendBundle:Search:search.html.twig', [
                'paginator' => $paginator,
                'searchText' => $text
            ]);
        }
        return $this->redirectToRoute('coreshop_index');
    }

    /**
     * @return FormInterface
     */
    protected function createSearchForm(): FormInterface
    {
        return $form = $this->get('form.factory')->createNamed('search', SearchType::class, null, [
            'action' => $this->generateUrl('coreshop_search'),
            'method' => 'GET'
        ]);
    }
}
