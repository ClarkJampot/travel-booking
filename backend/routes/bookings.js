import express from "express";
import { query } from "../models/db.js";
import { requireAuth } from "./_auth.js";

const router = express.Router();

// Utility: parse positive integer with fallback
function parsePositiveInt(value, fallback) {
  const n = parseInt(value, 10);
  return Number.isFinite(n) && n > 0 ? n : fallback;
}

// GET /api/bookings?page=&limit=&userId=
router.get("/", async (req, res) => {
  try {
    const page = parsePositiveInt(req.query.page, 1);
    const limit = Math.min(parsePositiveInt(req.query.limit, 10), 50);
    const offset = (page - 1) * limit;
    const { userId } = req.query;

    const filters = [];
    const params = [];
    if (userId) {
      filters.push("user_id = ?");
      params.push(userId);
    }
    const where = filters.length ? `WHERE ${filters.join(" AND ")}` : "";

    const rows = await query(
      `SELECT * FROM bookings ${where} ORDER BY booked_at DESC, id DESC LIMIT ${limit} OFFSET ${offset}`,
      params,
    );
    res.json({ page, limit, results: rows });
  } catch (err) {
    console.error("Error listing bookings:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

// GET /api/bookings/:id
router.get("/:id", async (req, res) => {
  try {
    const id = parseInt(req.params.id, 10);
    const rows = await query("SELECT * FROM bookings WHERE id = ?", [id]);
    if (!rows.length) return res.status(404).json({ error: "Not found" });
    res.json(rows[0]);
  } catch (err) {
    console.error("Error fetching booking:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

// POST /api/bookings
// Body: { userId, itemType: 'hotel'|'flight'|'activity'|'transfer', itemId, totalPrice }
router.post("/", requireAuth, async (req, res) => {
  try {
    const { userId, itemType, itemId, totalPrice } = req.body || {};
    const validTypes = ["hotel", "flight", "activity", "transfer"];
    if (!userId || !itemType || !itemId || totalPrice == null) {
      return res.status(400).json({ error: "Missing required fields" });
    }
    if (!validTypes.includes(itemType)) {
      return res.status(400).json({ error: "Invalid itemType" });
    }
    const price = Number(totalPrice);
    if (!Number.isFinite(price) || price < 0) {
      return res.status(400).json({ error: "Invalid totalPrice" });
    }

    const result = await query(
      "INSERT INTO bookings (user_id, item_type, item_id, total_price) VALUES (?, ?, ?, ?)",
      [userId, itemType, itemId, price],
    );
    const insertedId = result.insertId;
    const rows = await query("SELECT * FROM bookings WHERE id = ?", [insertedId]);
    res.status(201).json(rows[0]);
  } catch (err) {
    console.error("Error creating booking:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;



