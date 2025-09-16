import { query } from "../models/db.js";

export function requireAuth(req, res, next) {
  if (!req.session || !req.session.user) {
    return res.status(401).json({ error: "Unauthorized" });
  }
  next();
}

export function requireRole(roleNames) {
  const allowed = Array.isArray(roleNames) ? roleNames : [roleNames];
  return (req, res, next) => {
    if (!req.session || !req.session.user) {
      return res.status(401).json({ error: "Unauthorized" });
    }
    const userRole = req.session.user.role;
    if (!allowed.includes(userRole)) {
      return res.status(403).json({ error: "Forbidden" });
    }
    next();
  };
}

export async function findUserByEmail(email) {
  const rows = await query(
    `SELECT u.id, u.email, u.full_name, r.name AS role
     FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?`,
    [email],
  );
  return rows[0];
}


