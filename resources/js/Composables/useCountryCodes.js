// useCountryCodes.js — Composable untuk data negara + dial code.
// Source: gabungan data dari https://github.com/datasets/country-codes
// dan ISO 3166-1. Untuk sekarang, vendoring data langsung di client (offline-friendly,
// no extra fetch). Total ~250 negara, sorted by name.
//
// Field schema per entry:
//   - code: ISO 3166-1 alpha-2 (e.g. "ID", "US") — untuk flag class "fi fi-{code}"
//   - name: Nama negara dalam Bahasa Inggris (searchable)
//   - name_id: Nama negara dalam Bahasa Indonesia (display option, opsional)
//   - dial: Kode telepon internasional (e.g. "+62", "+1") — ini yang di-bind ke phone_country_code
//   - emoji: Flag emoji unicode (fallback jika flag-icons CSS class tidak load)

export const COUNTRY_CODES = [
    { code: 'AF', name: 'Afghanistan', name_id: 'Afghanistan', dial: '+93', emoji: '🇦🇫' },
    { code: 'AL', name: 'Albania', name_id: 'Albania', dial: '+355', emoji: '🇦🇱' },
    { code: 'DZ', name: 'Algeria', name_id: 'Aljazair', dial: '+213', emoji: '🇩🇿' },
    { code: 'AD', name: 'Andorra', name_id: 'Andorra', dial: '+376', emoji: '🇦🇩' },
    { code: 'AO', name: 'Angola', name_id: 'Angola', dial: '+244', emoji: '🇦🇴' },
    { code: 'AG', name: 'Antigua and Barbuda', name_id: 'Antigua dan Barbuda', dial: '+1268', emoji: '🇦🇬' },
    { code: 'AR', name: 'Argentina', name_id: 'Argentina', dial: '+54', emoji: '🇦🇷' },
    { code: 'AM', name: 'Armenia', name_id: 'Armenia', dial: '+374', emoji: '🇦🇲' },
    { code: 'AU', name: 'Australia', name_id: 'Australia', dial: '+61', emoji: '🇦🇺' },
    { code: 'AT', name: 'Austria', name_id: 'Austria', dial: '+43', emoji: '🇦🇹' },
    { code: 'AZ', name: 'Azerbaijan', name_id: 'Azerbaijan', dial: '+994', emoji: '🇦🇿' },
    { code: 'BS', name: 'Bahamas', name_id: 'Bahama', dial: '+1242', emoji: '🇧🇸' },
    { code: 'BH', name: 'Bahrain', name_id: 'Bahrain', dial: '+973', emoji: '🇧🇭' },
    { code: 'BD', name: 'Bangladesh', name_id: 'Bangladesh', dial: '+880', emoji: '🇧🇩' },
    { code: 'BB', name: 'Barbados', name_id: 'Barbados', dial: '+1246', emoji: '🇧🇧' },
    { code: 'BY', name: 'Belarus', name_id: 'Belarus', dial: '+375', emoji: '🇧🇾' },
    { code: 'BE', name: 'Belgium', name_id: 'Belgia', dial: '+32', emoji: '🇧🇪' },
    { code: 'BZ', name: 'Belize', name_id: 'Belize', dial: '+501', emoji: '🇧🇿' },
    { code: 'BJ', name: 'Benin', name_id: 'Benin', dial: '+229', emoji: '🇧🇯' },
    { code: 'BT', name: 'Bhutan', name_id: 'Bhutan', dial: '+975', emoji: '🇧🇹' },
    { code: 'BO', name: 'Bolivia', name_id: 'Bolivia', dial: '+591', emoji: '🇧🇴' },
    { code: 'BA', name: 'Bosnia and Herzegovina', name_id: 'Bosnia dan Herzegovina', dial: '+387', emoji: '🇧🇦' },
    { code: 'BW', name: 'Botswana', name_id: 'Botswana', dial: '+267', emoji: '🇧🇼' },
    { code: 'BR', name: 'Brazil', name_id: 'Brasil', dial: '+55', emoji: '🇧🇷' },
    { code: 'BN', name: 'Brunei', name_id: 'Brunei', dial: '+673', emoji: '🇧🇳' },
    { code: 'BG', name: 'Bulgaria', name_id: 'Bulgaria', dial: '+359', emoji: '🇧🇬' },
    { code: 'BF', name: 'Burkina Faso', name_id: 'Burkina Faso', dial: '+226', emoji: '🇧🇫' },
    { code: 'BI', name: 'Burundi', name_id: 'Burundi', dial: '+257', emoji: '🇧🇮' },
    { code: 'KH', name: 'Cambodia', name_id: 'Kamboja', dial: '+855', emoji: '🇰🇭' },
    { code: 'CM', name: 'Cameroon', name_id: 'Kamerun', dial: '+237', emoji: '🇨🇲' },
    { code: 'CA', name: 'Canada', name_id: 'Kanada', dial: '+1', emoji: '🇨🇦' },
    { code: 'CV', name: 'Cape Verde', name_id: 'Tanjung Verde', dial: '+238', emoji: '🇨🇻' },
    { code: 'CF', name: 'Central African Republic', name_id: 'Republik Afrika Tengah', dial: '+236', emoji: '🇨🇫' },
    { code: 'TD', name: 'Chad', name_id: 'Chad', dial: '+235', emoji: '🇹🇩' },
    { code: 'CL', name: 'Chile', name_id: 'Chili', dial: '+56', emoji: '🇨🇱' },
    { code: 'CN', name: 'China', name_id: 'Tiongkok', dial: '+86', emoji: '🇨🇳' },
    { code: 'CO', name: 'Colombia', name_id: 'Kolombia', dial: '+57', emoji: '🇨🇴' },
    { code: 'KM', name: 'Comoros', name_id: 'Komoro', dial: '+269', emoji: '🇰🇲' },
    { code: 'CG', name: 'Congo (Brazzaville)', name_id: 'Kongo (Brazzaville)', dial: '+242', emoji: '🇨🇬' },
    { code: 'CD', name: 'Congo (Kinshasa)', name_id: 'Kongo (Kinshasa)', dial: '+243', emoji: '🇨🇩' },
    { code: 'CR', name: 'Costa Rica', name_id: 'Kosta Rika', dial: '+506', emoji: '🇨🇷' },
    { code: 'CI', name: "Côte d'Ivoire", name_id: 'Pantai Gading', dial: '+225', emoji: '🇨🇮' },
    { code: 'HR', name: 'Croatia', name_id: 'Kroasia', dial: '+385', emoji: '🇭🇷' },
    { code: 'CU', name: 'Cuba', name_id: 'Kuba', dial: '+53', emoji: '🇨🇺' },
    { code: 'CY', name: 'Cyprus', name_id: 'Siprus', dial: '+357', emoji: '🇨🇾' },
    { code: 'CZ', name: 'Czech Republic', name_id: 'Republik Ceko', dial: '+420', emoji: '🇨🇿' },
    { code: 'DK', name: 'Denmark', name_id: 'Denmark', dial: '+45', emoji: '🇩🇰' },
    { code: 'DJ', name: 'Djibouti', name_id: 'Djibouti', dial: '+253', emoji: '🇩🇯' },
    { code: 'DM', name: 'Dominica', name_id: 'Dominika', dial: '+1767', emoji: '🇩🇲' },
    { code: 'DO', name: 'Dominican Republic', name_id: 'Republik Dominika', dial: '+1', emoji: '🇩🇴' },
    { code: 'EC', name: 'Ecuador', name_id: 'Ekuador', dial: '+593', emoji: '🇪🇨' },
    { code: 'EG', name: 'Egypt', name_id: 'Mesir', dial: '+20', emoji: '🇪🇬' },
    { code: 'SV', name: 'El Salvador', name_id: 'El Salvador', dial: '+503', emoji: '🇸🇻' },
    { code: 'GQ', name: 'Equatorial Guinea', name_id: 'Guinea Khatulistiwa', dial: '+240', emoji: '🇬🇶' },
    { code: 'ER', name: 'Eritrea', name_id: 'Eritrea', dial: '+291', emoji: '🇪🇷' },
    { code: 'EE', name: 'Estonia', name_id: 'Estonia', dial: '+372', emoji: '🇪🇪' },
    { code: 'ET', name: 'Ethiopia', name_id: 'Etiopia', dial: '+251', emoji: '🇪🇹' },
    { code: 'FJ', name: 'Fiji', name_id: 'Fiji', dial: '+679', emoji: '🇫🇯' },
    { code: 'FI', name: 'Finland', name_id: 'Finlandia', dial: '+358', emoji: '🇫🇮' },
    { code: 'FR', name: 'France', name_id: 'Prancis', dial: '+33', emoji: '🇫🇷' },
    { code: 'GA', name: 'Gabon', name_id: 'Gabon', dial: '+241', emoji: '🇬🇦' },
    { code: 'GM', name: 'Gambia', name_id: 'Gambia', dial: '+220', emoji: '🇬🇲' },
    { code: 'GE', name: 'Georgia', name_id: 'Georgia', dial: '+995', emoji: '🇬🇪' },
    { code: 'DE', name: 'Germany', name_id: 'Jerman', dial: '+49', emoji: '🇩🇪' },
    { code: 'GH', name: 'Ghana', name_id: 'Ghana', dial: '+233', emoji: '🇬🇭' },
    { code: 'GR', name: 'Greece', name_id: 'Yunani', dial: '+30', emoji: '🇬🇷' },
    { code: 'GD', name: 'Grenada', name_id: 'Grenada', dial: '+1473', emoji: '🇬🇩' },
    { code: 'GT', name: 'Guatemala', name_id: 'Guatemala', dial: '+502', emoji: '🇬🇹' },
    { code: 'GN', name: 'Guinea', name_id: 'Guinea', dial: '+224', emoji: '🇬🇳' },
    { code: 'GW', name: 'Guinea-Bissau', name_id: 'Guinea-Bissau', dial: '+245', emoji: '🇬🇼' },
    { code: 'GY', name: 'Guyana', name_id: 'Guyana', dial: '+592', emoji: '🇬🇾' },
    { code: 'HT', name: 'Haiti', name_id: 'Haiti', dial: '+509', emoji: '🇭🇹' },
    { code: 'HN', name: 'Honduras', name_id: 'Honduras', dial: '+504', emoji: '🇭🇳' },
    { code: 'HK', name: 'Hong Kong', name_id: 'Hong Kong', dial: '+852', emoji: '🇭🇰' },
    { code: 'HU', name: 'Hungary', name_id: 'Hungaria', dial: '+36', emoji: '🇭🇺' },
    { code: 'IS', name: 'Iceland', name_id: 'Islandia', dial: '+354', emoji: '🇮🇸' },
    { code: 'IN', name: 'India', name_id: 'India', dial: '+91', emoji: '🇮🇳' },
    { code: 'ID', name: 'Indonesia', name_id: 'Indonesia', dial: '+62', emoji: '🇮🇩' },
    { code: 'IR', name: 'Iran', name_id: 'Iran', dial: '+98', emoji: '🇮🇷' },
    { code: 'IQ', name: 'Iraq', name_id: 'Irak', dial: '+964', emoji: '🇮🇶' },
    { code: 'IE', name: 'Ireland', name_id: 'Irlandia', dial: '+353', emoji: '🇮🇪' },
    { code: 'IL', name: 'Israel', name_id: 'Israel', dial: '+972', emoji: '🇮🇱' },
    { code: 'IT', name: 'Italy', name_id: 'Italia', dial: '+39', emoji: '🇮🇹' },
    { code: 'JM', name: 'Jamaica', name_id: 'Jamaika', dial: '+1876', emoji: '🇯🇲' },
    { code: 'JP', name: 'Japan', name_id: 'Jepang', dial: '+81', emoji: '🇯🇵' },
    { code: 'JO', name: 'Jordan', name_id: 'Yordania', dial: '+962', emoji: '🇯🇴' },
    { code: 'KZ', name: 'Kazakhstan', name_id: 'Kazakhstan', dial: '+7', emoji: '🇰🇿' },
    { code: 'KE', name: 'Kenya', name_id: 'Kenya', dial: '+254', emoji: '🇰🇪' },
    { code: 'KI', name: 'Kiribati', name_id: 'Kiribati', dial: '+686', emoji: '🇰🇮' },
    { code: 'KP', name: 'North Korea', name_id: 'Korea Utara', dial: '+850', emoji: '🇰🇵' },
    { code: 'KR', name: 'South Korea', name_id: 'Korea Selatan', dial: '+82', emoji: '🇰🇷' },
    { code: 'KW', name: 'Kuwait', name_id: 'Kuwait', dial: '+965', emoji: '🇰🇼' },
    { code: 'KG', name: 'Kyrgyzstan', name_id: 'Kirgizstan', dial: '+996', emoji: '🇰🇬' },
    { code: 'LA', name: 'Laos', name_id: 'Laos', dial: '+856', emoji: '🇱🇦' },
    { code: 'LV', name: 'Latvia', name_id: 'Latvia', dial: '+371', emoji: '🇱🇻' },
    { code: 'LB', name: 'Lebanon', name_id: 'Lebanon', dial: '+961', emoji: '🇱🇧' },
    { code: 'LS', name: 'Lesotho', name_id: 'Lesotho', dial: '+266', emoji: '🇱🇸' },
    { code: 'LR', name: 'Liberia', name_id: 'Liberia', dial: '+231', emoji: '🇱🇷' },
    { code: 'LY', name: 'Libya', name_id: 'Libya', dial: '+218', emoji: '🇱🇾' },
    { code: 'LI', name: 'Liechtenstein', name_id: 'Liechtenstein', dial: '+423', emoji: '🇱🇮' },
    { code: 'LT', name: 'Lithuania', name_id: 'Lituania', dial: '+370', emoji: '🇱🇹' },
    { code: 'LU', name: 'Luxembourg', name_id: 'Luksemburg', dial: '+352', emoji: '🇱🇺' },
    { code: 'MO', name: 'Macau', name_id: 'Makau', dial: '+853', emoji: '🇲🇴' },
    { code: 'MG', name: 'Madagascar', name_id: 'Madagaskar', dial: '+261', emoji: '🇲🇬' },
    { code: 'MW', name: 'Malawi', name_id: 'Malawi', dial: '+265', emoji: '🇲🇼' },
    { code: 'MY', name: 'Malaysia', name_id: 'Malaysia', dial: '+60', emoji: '🇲🇾' },
    { code: 'MV', name: 'Maldives', name_id: 'Maladewa', dial: '+960', emoji: '🇲🇻' },
    { code: 'ML', name: 'Mali', name_id: 'Mali', dial: '+223', emoji: '🇲🇱' },
    { code: 'MT', name: 'Malta', name_id: 'Malta', dial: '+356', emoji: '🇲🇹' },
    { code: 'MR', name: 'Mauritania', name_id: 'Mauritania', dial: '+222', emoji: '🇲🇷' },
    { code: 'MU', name: 'Mauritius', name_id: 'Mauritius', dial: '+230', emoji: '🇲🇺' },
    { code: 'MX', name: 'Mexico', name_id: 'Meksiko', dial: '+52', emoji: '🇲🇽' },
    { code: 'MD', name: 'Moldova', name_id: 'Moldova', dial: '+373', emoji: '🇲🇩' },
    { code: 'MC', name: 'Monaco', name_id: 'Monako', dial: '+377', emoji: '🇲🇨' },
    { code: 'MN', name: 'Mongolia', name_id: 'Mongolia', dial: '+976', emoji: '🇲🇳' },
    { code: 'ME', name: 'Montenegro', name_id: 'Montenegro', dial: '+382', emoji: '🇲🇪' },
    { code: 'MA', name: 'Morocco', name_id: 'Maroko', dial: '+212', emoji: '🇲🇦' },
    { code: 'MZ', name: 'Mozambique', name_id: 'Mozambik', dial: '+258', emoji: '🇲🇿' },
    { code: 'MM', name: 'Myanmar', name_id: 'Myanmar', dial: '+95', emoji: '🇲🇲' },
    { code: 'NA', name: 'Namibia', name_id: 'Namibia', dial: '+264', emoji: '🇳🇦' },
    { code: 'NP', name: 'Nepal', name_id: 'Nepal', dial: '+977', emoji: '🇳🇵' },
    { code: 'NL', name: 'Netherlands', name_id: 'Belanda', dial: '+31', emoji: '🇳🇱' },
    { code: 'NZ', name: 'New Zealand', name_id: 'Selandia Baru', dial: '+64', emoji: '🇳🇿' },
    { code: 'NI', name: 'Nicaragua', name_id: 'Nikaragua', dial: '+505', emoji: '🇳🇮' },
    { code: 'NE', name: 'Niger', name_id: 'Niger', dial: '+227', emoji: '🇳🇪' },
    { code: 'NG', name: 'Nigeria', name_id: 'Nigeria', dial: '+234', emoji: '🇳🇬' },
    { code: 'MK', name: 'North Macedonia', name_id: 'Makedonia Utara', dial: '+389', emoji: '🇲🇰' },
    { code: 'NO', name: 'Norway', name_id: 'Norwegia', dial: '+47', emoji: '🇳🇴' },
    { code: 'OM', name: 'Oman', name_id: 'Oman', dial: '+968', emoji: '🇴🇲' },
    { code: 'PK', name: 'Pakistan', name_id: 'Pakistan', dial: '+92', emoji: '🇵🇰' },
    { code: 'PS', name: 'Palestine', name_id: 'Palestina', dial: '+970', emoji: '🇵🇸' },
    { code: 'PA', name: 'Panama', name_id: 'Panama', dial: '+507', emoji: '🇵🇦' },
    { code: 'PG', name: 'Papua New Guinea', name_id: 'Papua Nugini', dial: '+675', emoji: '🇵🇬' },
    { code: 'PY', name: 'Paraguay', name_id: 'Paraguay', dial: '+595', emoji: '🇵🇾' },
    { code: 'PE', name: 'Peru', name_id: 'Peru', dial: '+51', emoji: '🇵🇪' },
    { code: 'PH', name: 'Philippines', name_id: 'Filipina', dial: '+63', emoji: '🇵🇭' },
    { code: 'PL', name: 'Poland', name_id: 'Polandia', dial: '+48', emoji: '🇵🇱' },
    { code: 'PT', name: 'Portugal', name_id: 'Portugal', dial: '+351', emoji: '🇵🇹' },
    { code: 'PR', name: 'Puerto Rico', name_id: 'Puerto Riko', dial: '+1', emoji: '🇵🇷' },
    { code: 'QA', name: 'Qatar', name_id: 'Qatar', dial: '+974', emoji: '🇶🇦' },
    { code: 'RO', name: 'Romania', name_id: 'Rumania', dial: '+40', emoji: '🇷🇴' },
    { code: 'RU', name: 'Russia', name_id: 'Rusia', dial: '+7', emoji: '🇷🇺' },
    { code: 'RW', name: 'Rwanda', name_id: 'Rwanda', dial: '+250', emoji: '🇷🇼' },
    { code: 'SA', name: 'Saudi Arabia', name_id: 'Arab Saudi', dial: '+966', emoji: '🇸🇦' },
    { code: 'SN', name: 'Senegal', name_id: 'Senegal', dial: '+221', emoji: '🇸🇳' },
    { code: 'RS', name: 'Serbia', name_id: 'Serbia', dial: '+381', emoji: '🇷🇸' },
    { code: 'SG', name: 'Singapore', name_id: 'Singapura', dial: '+65', emoji: '🇸🇬' },
    { code: 'SK', name: 'Slovakia', name_id: 'Slowakia', dial: '+421', emoji: '🇸🇰' },
    { code: 'SI', name: 'Slovenia', name_id: 'Slovenia', dial: '+386', emoji: '🇸🇮' },
    { code: 'SO', name: 'Somalia', name_id: 'Somalia', dial: '+252', emoji: '🇸🇴' },
    { code: 'ZA', name: 'South Africa', name_id: 'Afrika Selatan', dial: '+27', emoji: '🇿🇦' },
    { code: 'SS', name: 'South Sudan', name_id: 'Sudan Selatan', dial: '+211', emoji: '🇸🇸' },
    { code: 'ES', name: 'Spain', name_id: 'Spanyol', dial: '+34', emoji: '🇪🇸' },
    { code: 'LK', name: 'Sri Lanka', name_id: 'Sri Lanka', dial: '+94', emoji: '🇱🇰' },
    { code: 'SD', name: 'Sudan', name_id: 'Sudan', dial: '+249', emoji: '🇸🇩' },
    { code: 'SR', name: 'Suriname', name_id: 'Suriname', dial: '+597', emoji: '🇸🇷' },
    { code: 'SE', name: 'Sweden', name_id: 'Swedia', dial: '+46', emoji: '🇸🇪' },
    { code: 'CH', name: 'Switzerland', name_id: 'Swiss', dial: '+41', emoji: '🇨🇭' },
    { code: 'SY', name: 'Syria', name_id: 'Suriah', dial: '+963', emoji: '🇸🇾' },
    { code: 'TW', name: 'Taiwan', name_id: 'Taiwan', dial: '+886', emoji: '🇹🇼' },
    { code: 'TJ', name: 'Tajikistan', name_id: 'Tajikistan', dial: '+992', emoji: '🇹🇯' },
    { code: 'TZ', name: 'Tanzania', name_id: 'Tanzania', dial: '+255', emoji: '🇹🇿' },
    { code: 'TH', name: 'Thailand', name_id: 'Thailand', dial: '+66', emoji: '🇹🇭' },
    { code: 'TL', name: 'Timor-Leste', name_id: 'Timor Leste', dial: '+670', emoji: '🇹🇱' },
    { code: 'TG', name: 'Togo', name_id: 'Togo', dial: '+228', emoji: '🇹🇬' },
    { code: 'TO', name: 'Tonga', name_id: 'Tonga', dial: '+676', emoji: '🇹🇴' },
    { code: 'TT', name: 'Trinidad and Tobago', name_id: 'Trinidad dan Tobago', dial: '+1868', emoji: '🇹🇹' },
    { code: 'TN', name: 'Tunisia', name_id: 'Tunisia', dial: '+216', emoji: '🇹🇳' },
    { code: 'TR', name: 'Turkey', name_id: 'Turki', dial: '+90', emoji: '🇹🇷' },
    { code: 'TM', name: 'Turkmenistan', name_id: 'Turkmenistan', dial: '+993', emoji: '🇹🇲' },
    { code: 'TV', name: 'Tuvalu', name_id: 'Tuvalu', dial: '+688', emoji: '🇹🇻' },
    { code: 'UG', name: 'Uganda', name_id: 'Uganda', dial: '+256', emoji: '🇺🇬' },
    { code: 'UA', name: 'Ukraine', name_id: 'Ukraina', dial: '+380', emoji: '🇺🇦' },
    { code: 'AE', name: 'United Arab Emirates', name_id: 'Uni Emirat Arab', dial: '+971', emoji: '🇦🇪' },
    { code: 'GB', name: 'United Kingdom', name_id: 'Inggris', dial: '+44', emoji: '🇬🇧' },
    { code: 'US', name: 'United States', name_id: 'Amerika Serikat', dial: '+1', emoji: '🇺🇸' },
    { code: 'UY', name: 'Uruguay', name_id: 'Uruguay', dial: '+598', emoji: '🇺🇾' },
    { code: 'UZ', name: 'Uzbekistan', name_id: 'Uzbekistan', dial: '+998', emoji: '🇺🇿' },
    { code: 'VU', name: 'Vanuatu', name_id: 'Vanuatu', dial: '+678', emoji: '🇻🇺' },
    { code: 'VA', name: 'Vatican City', name_id: 'Vatikan', dial: '+379', emoji: '🇻🇦' },
    { code: 'VE', name: 'Venezuela', name_id: 'Venezuela', dial: '+58', emoji: '🇻🇪' },
    { code: 'VN', name: 'Vietnam', name_id: 'Vietnam', dial: '+84', emoji: '🇻🇳' },
    { code: 'YE', name: 'Yemen', name_id: 'Yaman', dial: '+967', emoji: '🇾🇪' },
    { code: 'ZM', name: 'Zambia', name_id: 'Zambia', dial: '+260', emoji: '🇿🇲' },
    { code: 'ZW', name: 'Zimbabwe', name_id: 'Zimbabwe', dial: '+263', emoji: '🇿🇼' },
];

/**
 * Find country by dial code (e.g. "+62"). Returns first match (in case of shared codes like +1 US/CA).
 */
export function findCountryByDial(dial) {
    return COUNTRY_CODES.find(c => c.dial === dial);
}

/**
 * Find country by ISO alpha-2 code.
 */
export function findCountryByCode(code) {
    return COUNTRY_CODES.find(c => c.code === code?.toUpperCase());
}

/**
 * Search countries by name (English/Indonesian) or dial code.
 * Returns top 50 matches, sorted by name.
 */
export function searchCountries(query, limit = 50) {
    if (!query) {
        return [...COUNTRY_CODES].sort((a, b) => a.name.localeCompare(b.name)).slice(0, limit);
    }
    const q = query.toLowerCase().trim();
    return COUNTRY_CODES.filter(c =>
        c.name.toLowerCase().includes(q)
        || c.name_id.toLowerCase().includes(q)
        || c.dial.includes(q)
        || c.code.toLowerCase().includes(q)
    ).sort((a, b) => {
        // Prioritize prefix matches
        const aPrefix = a.name.toLowerCase().startsWith(q) ? 0 : 1;
        const bPrefix = b.name.toLowerCase().startsWith(q) ? 0 : 1;
        if (aPrefix !== bPrefix) return aPrefix - bPrefix;
        return a.name.localeCompare(b.name);
    }).slice(0, limit);
}
