/**
 * Location helper functions for cascading dropdowns
 */

/**
 * Initialize cascading city dropdown based on province selection
 * @param {string} provinceSelectId - Province select element ID
 * @param {string} citySelectId - City select element ID
 */
function initCascadingCityDropdown(provinceSelectId, citySelectId) {
  const provinceSelect = document.getElementById(provinceSelectId);
  const citySelect = document.getElementById(citySelectId);
  
  if (!provinceSelect || !citySelect) return;
  
  const initialProvinceId = provinceSelect.value;
  if (initialProvinceId) {
    citySelect.disabled = false;
    loadCitiesForProvince(initialProvinceId, citySelect);
  } else {
    citySelect.disabled = true;
    citySelect.innerHTML = '<option value="">Select Province First</option>';
  }
  
  provinceSelect.addEventListener('change', async function() {
    const provinceId = this.value;
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    if (!provinceId) {
      citySelect.disabled = true;
      citySelect.innerHTML = '<option value="">Select Province First</option>';
      return;
    }
    
    citySelect.disabled = false;
    await loadCitiesForProvince(provinceId, citySelect);
  });
}

/**
 * Load cities for a specific province
 * @param {string|number} provinceId - Province ID
 * @param {HTMLElement} citySelect - City select element
 */
async function loadCitiesForProvince(provinceId, citySelect) {
  try {
    const cities = await apiCall(`/cities?province_id=${provinceId}`);
    if (cities.results && cities.results.length > 0) {
      cities.results.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading cities:', error);
  }
}

/**
 * Load all cities into a select element
 * @param {HTMLElement} citySelect - City select element
 */
async function loadAllCities(citySelect) {
  try {
    const cities = await apiCall('/cities');
    if (cities.results && cities.results.length > 0) {
      cities.results.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading cities:', error);
  }
}

/**
 * Force integer-only values on numeric inputs
 * @param {string} inputId - Input element ID
 */
function enforceIntegerInput(inputId) {
  const el = document.getElementById(inputId);
  if (!el) return;
  const sanitize = () => {
    const cleaned = el.value.replace(/[^0-9]/g, '');
    el.value = cleaned;
  };
  el.addEventListener('input', sanitize);
  el.addEventListener('change', sanitize);
  sanitize();
}
