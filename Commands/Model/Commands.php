<?php

namespace Kitchen365\Commands\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Commands extends Command
{
    protected $customerRepository;
    protected $customerCollectionFactory;
    protected $transportBuilder;
    protected $storeManager;
    protected $appState;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        State $appState
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->appState = $appState;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('email:send')
             ->setDescription('Custom Command to send emails to all customers');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            // Set the area code to adminhtml to avoid area code issues
            $this->appState->setAreaCode('adminhtml');

            // Get all customers
            $customerCollection = $this->customerCollectionFactory->create();

            // Loop through each customer
            foreach ($customerCollection as $customer) {
                $this->sendEmailToCustomer($customer->getEmail(), $customer->getFirstname());
            }

            $output->writeln('Emails have been sent to all customers.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function sendEmailToCustomer($customerEmail, $customerName)
    {
        try {
            // Set the email template identifier from admin
            // $templateId = 'your_custom_email_template_identifier'; // Replace with your actual template identifier
            $storeId = $this->storeManager->getStore()->getId();

            // Set the email variables
            $vars = [
                'customer_name' => $customerName,
            ];

            // Create the email transport
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(16)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFrom('general') // Email sender identifier as per the configuration (e.g., general, sales)
                ->addTo($customerEmail)
                ->getTransport();

            // Send the email
            $transport->sendMessage();
        } catch (LocalizedException $e) {
            // Handle exceptions if needed
            echo 'Failed to send email to ' . $customerEmail . ': ' . $e->getMessage() . PHP_EOL;
        }
    }
}
