<?php

namespace App;

enum TicketCategory: string
{
    // Universal
    case General = 'general';
    case AccountIssue = 'account_issue';
    case PaymentIssue = 'payment_issue';

    // E-Commerce
    case OrderIssue = 'order_issue';
    case ProductIssue = 'product_issue';
    case DeliveryIssue = 'delivery_issue';
    case RefundRequest = 'refund_request';

    // Real Estate
    case PropertyInquiry = 'property_inquiry';
    case ViewingIssue = 'viewing_issue';
    case AgentDispute = 'agent_dispute';
    case Documentation = 'documentation';

    // Paupahan (Rentals)
    case RentalAgreement = 'rental_agreement';
    case MaintenanceRequest = 'maintenance_request';
    case LandlordIssue = 'landlord_issue';
    case DepositIssue = 'deposit_issue';

    // Lipat Bahay (Movers)
    case BookingSchedule = 'booking_schedule';
    case DamageReport = 'damage_report';
    case MoverBehavior = 'mover_behavior';
    case PricingDispute = 'pricing_dispute';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Inquiry',
            self::AccountIssue => 'Account Issue',
            self::PaymentIssue => 'Payment Issue',
            self::OrderIssue => 'Order Issue',
            self::ProductIssue => 'Product Issue',
            self::DeliveryIssue => 'Delivery Issue',
            self::RefundRequest => 'Refund Request',
            self::PropertyInquiry => 'Property Inquiry',
            self::ViewingIssue => 'Viewing Issue',
            self::AgentDispute => 'Agent Dispute',
            self::Documentation => 'Documentation / Paperwork',
            self::RentalAgreement => 'Rental Agreement Issue',
            self::MaintenanceRequest => 'Maintenance Request',
            self::LandlordIssue => 'Landlord/Owner Issue',
            self::DepositIssue => 'Security Deposit Issue',
            self::BookingSchedule => 'Booking/Schedule Issue',
            self::DamageReport => 'Damage Report',
            self::MoverBehavior => 'Mover Behavior',
            self::PricingDispute => 'Pricing/Rate Dispute',
        };
    }

    /**
     * Get categories allowed for a specific sector.
     *
     * @return array<TicketCategory>
     */
    public static function forSector(?string $sector): array
    {
        $universal = [self::General, self::AccountIssue, self::PaymentIssue];

        return match ($sector) {
            'ecommerce' => [...$universal, self::OrderIssue, self::ProductIssue, self::DeliveryIssue, self::RefundRequest],
            'real_estate' => [...$universal, self::PropertyInquiry, self::ViewingIssue, self::AgentDispute, self::Documentation],
            'paupahan' => [...$universal, self::RentalAgreement, self::MaintenanceRequest, self::LandlordIssue, self::DepositIssue],
            'lipat_bahay' => [...$universal, self::BookingSchedule, self::DamageReport, self::MoverBehavior, self::PricingDispute],
            default => $universal,
        };
    }
}
