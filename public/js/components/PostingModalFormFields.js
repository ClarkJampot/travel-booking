/**
 * PostingModal Form Fields Configuration
 * Defines form field configurations for different entity types
 */

/**
 * Get form field definitions for a posting type
 * @param {string} type - Entity type (hotel, flight, activity, transfer)
 * @returns {array} Array of field definitions
 */
function getPostingFormFields(type) {
  const baseFields = {
    hotel: [
      { name: 'name', label: 'Hotel Name', type: 'text', required: true },
      { name: 'province_id', label: 'Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'city_id', label: 'City', type: 'select', required: true, options: 'cities', dependsOn: 'province_id' },
      { name: 'destination_id', label: 'Destination', type: 'select', required: false, options: 'destinations' },
      { name: 'price_per_night', label: 'Price per Night', type: 'number', required: true, min: 0, step: 0.01 },
      { name: 'description', label: 'Description', type: 'textarea', required: false },
      { name: 'images', label: 'Images', type: 'fileupload', required: false },
      { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
      { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
    ],
    flight: [
      { name: 'origin_province_id', label: 'Origin Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'origin_city_id', label: 'Origin City', type: 'select', required: true, options: 'cities', dependsOn: 'origin_province_id' },
      { name: 'destination_province_id', label: 'Destination Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'destination_city_id', label: 'Destination City', type: 'select', required: true, options: 'cities', dependsOn: 'destination_province_id' },
      { name: 'base_price_economy', label: 'Economy Price', type: 'number', required: true, min: 0, step: 0.01 },
      { name: 'base_price_business', label: 'Business Price', type: 'number', required: false, min: 0, step: 0.01 },
      { name: 'base_price_first', label: 'First Class Price', type: 'number', required: false, min: 0, step: 0.01 },
      { name: 'aircraft_type', label: 'Aircraft Type', type: 'text', required: false },
      { name: 'departure_time', label: 'Departure Time', type: 'time', required: true },
      { name: 'trip_type', label: 'Trip Type', type: 'select', required: false, options: [{ value: 'one-way', label: 'One-way' }, { value: 'round-trip', label: 'Round-trip' }] },
      { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
      { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
    ],
    activity: [
      { name: 'title', label: 'Activity Title', type: 'text', required: true },
      { name: 'province_id', label: 'Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'city_id', label: 'City', type: 'select', required: true, options: 'cities', dependsOn: 'province_id' },
      { name: 'destination_id', label: 'Destination', type: 'select', required: false, options: 'destinations' },
      { name: 'price', label: 'Price', type: 'number', required: true, min: 0, step: 0.01 },
      { name: 'description', label: 'Description', type: 'textarea', required: false },
      { name: 'images', label: 'Images', type: 'fileupload', required: false },
      { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
      { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
    ],
    transfer: [
      { name: 'origin_province_id', label: 'Origin Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'origin_city_id', label: 'Origin City', type: 'select', required: true, options: 'cities', dependsOn: 'origin_province_id' },
      { name: 'origin_specific', label: 'Origin Specific Location', type: 'text', required: false },
      { name: 'destination_province_id', label: 'Destination Province', type: 'select', required: true, options: 'provinces', onChange: 'loadCities' },
      { name: 'destination_city_id', label: 'Destination City', type: 'select', required: true, options: 'cities', dependsOn: 'destination_province_id' },
      { name: 'destination_specific', label: 'Destination Specific Location', type: 'text', required: false },
      { name: 'base_price', label: 'Base Price', type: 'number', required: true, min: 0, step: 0.01 },
      { name: 'departure_time', label: 'Departure Time', type: 'time', required: true },
      { name: 'description', label: 'Description', type: 'textarea', required: false },
      { name: 'ad', label: 'Promoted', type: 'checkbox', required: false },
      { name: 'discount_percent', label: 'Discount %', type: 'number', required: false, min: 0, max: 100, step: 0.01 }
    ]
  };
  
  return baseFields[type] || [];
}

