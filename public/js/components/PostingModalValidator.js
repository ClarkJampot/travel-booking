/**
 * PostingModal Validator
 * Handles form validation for different entity types
 */

/**
 * Validate form data before submission
 * @param {object} data - Form data
 * @param {string} type - Entity type
 * @returns {array} Array of error messages
 */
function validatePostingFormData(data, type) {
  const errors = [];
  
  if (type === 'flight') {
    if (!data.airline || data.airline.trim() === '') {
      errors.push('Airline is required');
    }
    if (!data.origin_province_id || !data.origin_city_id) {
      errors.push('Origin province and city are required');
    }
    if (!data.destination_province_id || !data.destination_city_id) {
      errors.push('Destination province and city are required');
    }
    if (data.origin_city_id === data.destination_city_id && data.origin_province_id === data.destination_province_id) {
      errors.push('Origin and destination cannot be the same');
    }
    if (!data.base_price_economy || data.base_price_economy <= 0) {
      errors.push('Economy price is required and must be greater than 0');
    }
    if (data.base_price_economy && data.base_price_economy > 99999999.99) {
      errors.push('Economy price is too large (maximum: 99,999,999.99)');
    }
    if (data.base_price_business && data.base_price_business <= 0) {
      errors.push('Business price must be greater than 0');
    }
    if (data.base_price_business && data.base_price_business > 99999999.99) {
      errors.push('Business price is too large (maximum: 99,999,999.99)');
    }
    if (data.base_price_first && data.base_price_first <= 0) {
      errors.push('First class price must be greater than 0');
    }
    if (data.base_price_first && data.base_price_first > 99999999.99) {
      errors.push('First class price is too large (maximum: 99,999,999.99)');
    }
    if (!data.departure_time) {
      errors.push('Departure time is required');
    }
    if (data.discount_percent < 0 || data.discount_percent > 100) {
      errors.push('Discount percent must be between 0 and 100');
    }
  } else if (type === 'transfer') {
    if (!data.origin_city_id) {
      errors.push('Origin city is required');
    }
    if (!data.destination_city_id) {
      errors.push('Destination city is required');
    }
    if (data.origin_city_id === data.destination_city_id && !data.origin_specific && !data.destination_specific) {
      errors.push('Origin and destination cities cannot be the same without specific locations');
    }
    if (!data.base_price || data.base_price <= 0) {
      errors.push('Base price is required and must be greater than 0');
    }
    if (data.base_price && data.base_price > 99999999.99) {
      errors.push('Base price is too large (maximum: 99,999,999.99)');
    }
    if (!data.departure_time) {
      errors.push('Departure time is required');
    }
    if (data.discount_percent < 0 || data.discount_percent > 100) {
      errors.push('Discount percent must be between 0 and 100');
    }
  } else if (type === 'hotel') {
    if (!data.name || data.name.trim() === '') {
      errors.push('Hotel name is required');
    }
    if (!data.province_id || !data.city_id) {
      errors.push('Province and city are required');
    }
    if (!data.price_per_night || data.price_per_night <= 0) {
      errors.push('Price per night is required and must be greater than 0');
    }
    if (data.price_per_night && data.price_per_night > 99999999.99) {
      errors.push('Price per night is too large (maximum: 99,999,999.99)');
    }
    if (data.discount_percent < 0 || data.discount_percent > 100) {
      errors.push('Discount percent must be between 0 and 100');
    }
  } else if (type === 'activity') {
    if (!data.title || data.title.trim() === '') {
      errors.push('Activity title is required');
    }
    if (!data.province_id || !data.city_id) {
      errors.push('Province and city are required');
    }
    if (!data.price || data.price <= 0) {
      errors.push('Price is required and must be greater than 0');
    }
    if (data.price && data.price > 99999999.99) {
      errors.push('Price is too large (maximum: 99,999,999.99)');
    }
    if (data.discount_percent < 0 || data.discount_percent > 100) {
      errors.push('Discount percent must be between 0 and 100');
    }
  }
  
  return errors;
}

