<?php
namespace Zoho\ZeptoMail\Helper;

class ZConstants {
	
	const ident_general = 'ident_general';
	const ident_sales = 'ident_sales';
	const ident_support = 'ident_support';
	const ident_custom1 = 'ident_custom1';
	const ident_custom2 = 'ident_custom2';
	
	public static $email_types = [
							[	
								'id' => ZConstants::ident_general,
								'type' => 'General',
								'param_name' => 'zepto_ident_general'
							],
							[	
								'id' => ZConstants::ident_sales,
								'type' => 'Sales',
								'param_name' => 'zepto_ident_sales'
							],
							[	
								'id' => ZConstants::ident_support,
								'type' => 'Support',
								'param_name' => 'zepto_ident_support'
							],
							[	
								'id' => ZConstants::ident_custom1,
								'type' => 'Custom1',
								'param_name' => 'zepto_ident_custom1'
							],
							[	
								'id' => ZConstants::ident_custom2,
								'type' => 'Custom2',
								'param_name' => 'zepto_ident_custom2'
							]

						 ];
}
	