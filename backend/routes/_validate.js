export function requireFields(required) {
  return function (req, res, next) {
    const missing = [];
    for (const key of required) {
      if (req.body == null || req.body[key] == null || req.body[key] === "") {
        missing.push(key);
      }
    }
    if (missing.length) {
      return res.status(400).json({ error: `Missing required fields: ${missing.join(", ")}` });
    }
    next();
  };
}

export function numberField(name, options = {}) {
  return function (req, res, next) {
    const value = req.body ? req.body[name] : undefined;
    if (value == null) return next();
    const num = Number(value);
    if (!Number.isFinite(num)) {
      return res.status(400).json({ error: `Field ${name} must be a number` });
    }
    if (options.min != null && num < options.min) {
      return res.status(400).json({ error: `Field ${name} must be >= ${options.min}` });
    }
    if (options.max != null && num > options.max) {
      return res.status(400).json({ error: `Field ${name} must be <= ${options.max}` });
    }
    next();
  };
}


