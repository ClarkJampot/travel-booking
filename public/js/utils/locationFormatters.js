/**
 * Location formatting utilities
 */

/**
 * Format city and province display
 * @param {object} options - Location options
 * @param {string} options.cityName - City name
 * @param {string} options.provinceName - Province name
 * @param {string} options.separator - Separator between city and province (default: ', ')
 * @returns {string} Formatted location string
 */
function formatLocation({ cityName = '', provinceName = '', separator = ', ' } = {}) {
  if (!cityName && !provinceName) return '';
  if (!cityName) return provinceName;
  if (!provinceName) return cityName;
  return `${cityName}${separator}${provinceName}`;
}

/**
 * Format route display (origin → destination)
 * @param {object} options - Route options
 * @param {string} options.origin - Origin location
 * @param {string} options.destination - Destination location
 * @param {string} options.originCode - Origin code (e.g., airport code)
 * @param {string} options.destinationCode - Destination code
 * @param {string} options.separator - Separator between location and code (default: ' (')
 * @param {string} options.routeSeparator - Separator between origin and destination (default: ' → ')
 * @returns {string} Formatted route string
 */
function formatRoute({ 
  origin = '', 
  destination = '', 
  originCode = '', 
  destinationCode = '',
  separator = ' (',
  routeSeparator = ' → '
} = {}) {
  const originDisplay = originCode 
    ? `${origin}${separator}${originCode})`
    : origin;
  const destinationDisplay = destinationCode
    ? `${destination}${separator}${destinationCode})`
    : destination;
  
  if (!originDisplay && !destinationDisplay) return '';
  if (!originDisplay) return destinationDisplay;
  if (!destinationDisplay) return originDisplay;
  
  return `${originDisplay}${routeSeparator}${destinationDisplay}`;
}

/**
 * Format location for hotel/activity cards
 * @param {object} item - Item data
 * @returns {string} Formatted location string
 */
function formatItemLocation(item) {
  return formatLocation({
    cityName: item.city_name || '',
    provinceName: item.province_name || ''
  });
}

/**
 * Format route for flight/transfer cards
 * @param {object} item - Item data
 * @returns {string} Formatted route string
 */
function formatItemRoute(item) {
  return formatRoute({
    origin: item.origin_city_name || item.origin || '',
    destination: item.destination_city_name || item.destination || '',
    originCode: item.origin_code || '',
    destinationCode: item.destination_code || ''
  });
}

