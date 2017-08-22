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

namespace CoreShop\Bundle\OrderBundle\Controller;

use Carbon\Carbon;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\SaleInterface;
use CoreShop\Component\Order\Processable\ProcessableInterface;
use CoreShop\Component\Order\Repository\OrderInvoiceRepositoryInterface;
use CoreShop\Component\Order\Repository\OrderShipmentRepositoryInterface;
use CoreShop\Component\Order\Workflow\WorkflowManagerInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Model\PaymentProviderInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Pimcore\Model\PimcoreModelInterface;
use CoreShop\Component\Resource\Repository\PimcoreRepositoryInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use Doctrine\ORM\EntityManager;
use Pimcore\Model\Object\Listing;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractSaleController
{
    /**
     * {@inheritdoc}
     */
    protected function getGridColumns(): array
    {
        return [
            [
                'text' => 'coreshop_order_state',
                'type' => null,
                'dataIndex' => 'orderState',
                'renderAs' => 'orderState',
                'width' => 200,
            ]
        ];
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function getStatesAction(Request $request): Response
    {
        $states = [];

        $list = new \Pimcore\Model\Workflow\Listing();
        $list->load();

        foreach ($list->getWorkflows() as $workflow) {
            if (is_array($workflow->getWorkflowSubject())) {
                $subject = $workflow->getWorkflowSubject();

                if (array_key_exists('classes', $subject)) {
                    if (in_array($this->getParameter('coreshop.model.order.pimcore_class_id'), $subject['classes'])) {
                        $states = $workflow->getStates();
                    }
                }
            }
        }

        return $this->json($states);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function updatePaymentAction(Request $request): Response
    {
        $payment = $this->getPaymentRepository()->find($request->get('id'));

        if (!$payment instanceof PaymentInterface) {
            return $this->json(['success' => false]);
        }

        $payment->setValues($request->request->all());

        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @return Response
     */
    public function getPaymentProvidersAction(): Response
    {
        $providers = $this->getPaymentProviderRepository()->findAll();
        $result = [];
        foreach ($providers as $provider) {
            if ($provider instanceof PaymentProviderInterface) {
                $result[] = [
                    'name' => $provider->getName(),
                    'id' => $provider->getId(),
                ];
            }
        }
        return $this->json(['success' => true, 'data' => $result]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function addPaymentAction(Request $request): Response
    {
        $orderId = $request->get('o_id');
        $order = $this->getSaleRepository()->find($orderId);
        $amount = doubleval($request->get('amount', 0));
        $transactionId = $request->get('transactionNumber');
        $paymentProviderId = $request->get('paymentProvider');

        if (!$order instanceof OrderInterface) {
            return $this->json(['success' => false, 'message' => 'Order with ID "' . $orderId . '" not found']);
        }

        $paymentProvider = $this->getPaymentProviderRepository()->find($paymentProviderId);

        if ($paymentProvider instanceof PaymentProviderInterface) {
            $payedTotal = $order->getTotalPayed();

            $payedTotal += $amount;

            if ($payedTotal > $order->getTotal()) {
                return $this->json(['success' => false, 'message' => 'Payed Amount is greater than order amount']);
            } else {
                /**
                 * @var PaymentInterface|PimcoreModelInterface
                 */
                $payment = $this->getPaymentFactory()->createNew();
                $payment->setNumber($transactionId);
                $payment->setPaymentProvider($paymentProvider);
                $payment->setCurrency($order->getCurrency());
                $payment->setTotalAmount($order->getTotal());
                $payment->setState(PaymentInterface::STATE_NEW);
                $payment->setDatePayment(Carbon::now());
                $payment->setOrderId($order->getId());

                $this->getEntityManager()->persist($payment);
                $this->getEntityManager()->flush();

                return $this->json(['success' => true, 'payments' => $this->getPayments($order), 'totalPayed' => $order->getTotalPayed()]);
            }
        } else {
            return $this->json(['success' => false, 'message' => "Payment Provider '$paymentProvider' not found"]);
        }
    }


    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    protected function getStatesHistory(OrderInterface $order): array
    {
        //Get History
        $manager = $this->getOrderStateManager();
        $history = $manager->getStateHistory($order);

        // create timeline
        $statesHistory = [];

        $date = Carbon::now();

        if (is_array($history)) {
            foreach ($history as $note) {
                $user = User::getById($note->getUser());
                $avatar = $user ? sprintf('/admin/user/get-image?id=%d', $user->getId()) : null;

                $statesHistory[] = [
                    'icon' => 'coreshop_icon_orderstates',
                    'type' => $note->getType(),
                    'date' => $date->formatLocalized('%A %d %B %Y'),
                    'avatar' => $avatar,
                    'user' => $user ? $user->getName() : null,
                    'description' => $note->getDescription(),
                    'title' => $note->getTitle(),
                    'data' => $note->getData(),
                ];
            }
        }

        return $statesHistory;
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    protected function getPayments(OrderInterface $order): array
    {
        $payments = $order->getPayments();
        $return = [];

        foreach ($payments as $payment) {
            //TODO: Whatever this was for
            //TODO: Actually, this was not bad at all, it saved history for payments, but needs to be different now
            //TODO: Payment Model has a detail array, where it can store any info
            /*$noteList = new \Pimcore\Model\Element\Note\Listing();
            $noteList->addConditionParam('type = ?', \CoreShop\Model\Order\Payment::NOTE_TRANSACTION);
            $noteList->addConditionParam('cid = ?', $payment->getId());
            $noteList->setOrderKey('date');
            $noteList->setOrder('desc');*/

            $return[] = [
                'id' => $payment->getId(),
                'datePayment' => $payment->getDatePayment() ? $payment->getDatePayment()->getTimestamp() : '',
                'provider' => $payment->getPaymentProvider()->getName(),
                'transactionIdentifier' => $payment->getNumber(),
                //'transactionNotes' => $noteList->load(),
                'amount' => $payment->getTotalAmount(),
                'state' => $payment->getState(),
            ];
        }

        return $return;
    }

    /**
     * @param SaleInterface $sale
     * @return array
     */
    protected function getDetails(SaleInterface $sale): array
    {
        $order = parent::getDetails($sale);

        if ($sale instanceof OrderInterface) {
            $order['statesHistory'] = $this->getStatesHistory($sale);
            $order['payments'] = $this->getPayments($sale);
            $order['editable'] = count($this->getInvoices($sale)) > 0 ? false : true;
            $order['invoices'] = $this->getInvoices($sale);
            $order['shipments'] = $this->getShipments($sale);
            $order['invoiceCreationAllowed'] = !$this->getInvoiceProcessableHelper()->isFullyProcessed($sale) && count($sale->getPayments()) !== 0;
            $order['shipmentCreationAllowed'] = !$this->getShipmentProcessableHelper()->isFullyProcessed($sale) && count($sale->getPayments()) !== 0;
        }

        return $order;
    }

    /**
     * @param SaleInterface $sale
     * @return array
     */
    protected function prepareSale(SaleInterface $sale): array
    {
        $order = parent::prepareSale($sale);

        if ($sale instanceof OrderInterface) {
            $order['orderState'] = $this->getOrderStateManager()->getCurrentState($sale);
            $order['paymentFee'] = $sale->getPaymentFee();
        }

        return $order;
    }


    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    protected function getInvoices($order): array
    {
        $invoices = $this->getOrderInvoiceRepository()->getDocuments($order);
        $invoiceArray = [];

        foreach ($invoices as $invoice) {
            $invoiceArray[] = $this->getDataForObject($invoice);
        }

        return $invoiceArray;
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    protected function getShipments($order): array
    {
        $invoices = $this->getOrderShipmentRepository()->getDocuments($order);
        $invoiceArray = [];

        foreach ($invoices as $invoice) {
            $invoiceArray[] = $this->getDataForObject($invoice);
        }

        return $invoiceArray;
    }

    /**
     * @param SaleInterface $sale
     * @return array
     */
    protected function getSummary(SaleInterface $sale): array
    {
        $summary = parent::getSummary($sale);

        if ($sale instanceof OrderInterface) {
            if ($sale->getPaymentFee() > 0) {
                $summary[] = [
                    'key' => 'payment',
                    'value' => $sale->getPaymentFee(),
                ];
            }
        }

        return $summary;
    }


    /**
     * @return ProcessableInterface
     */
    private function getInvoiceProcessableHelper(): ProcessableInterface
    {
        return $this->get('coreshop.order.invoice.processable');
    }

    /**
     * @return ProcessableInterface
     */
    private function getShipmentProcessableHelper(): ProcessableInterface
    {
        return $this->get('coreshop.order.shipment.processable');
    }

    /**
     * @return OrderInvoiceRepositoryInterface
     */
    private function getOrderInvoiceRepository(): OrderInvoiceRepositoryInterface
    {
        return $this->get('coreshop.repository.order_invoice');
    }

    /**
     * @return OrderShipmentRepositoryInterface
     */
    private function getOrderShipmentRepository(): OrderShipmentRepositoryInterface
    {
        return $this->get('coreshop.repository.order_shipment');
    }

    /**
     * @return WorkflowManagerInterface
     */
    private function getOrderStateManager(): WorkflowManagerInterface
    {
        return $this->get('coreshop.workflow.manager.order');
    }

    /**
     * @return RepositoryInterface
     */
    private function getPaymentRepository(): RepositoryInterface
    {
        return $this->get('coreshop.repository.payment');
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager(): EntityManager
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    /**
     * @return RepositoryInterface
     */
    private function getPaymentProviderRepository(): RepositoryInterface
    {
        return $this->get('coreshop.repository.payment_provider');
    }

    /**
     * @return FactoryInterface
     */
    private function getPaymentFactory(): FactoryInterface
    {
        return $this->get('coreshop.factory.payment');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSaleRepository(): PimcoreRepositoryInterface
    {
        return $this->get('coreshop.repository.order');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSalesList(): Listing
    {
        return $this->getSaleRepository()->getList();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSaleClassName(): string
    {
        return 'coreshop.model.order.pimcore_class_id';
    }

    /**
     * {@inheritdoc}
     */
    protected function getOrderKey(): string
    {
        return 'orderDate';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSaleNumberField(): string
    {
        return 'orderNumber';
    }
}
