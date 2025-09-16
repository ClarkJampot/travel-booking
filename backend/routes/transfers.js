import express from "express";
import { query } from "../models/db.js";
import { requireFields, numberField } from "./_validate.js";
import { requireAuth, requireRole } from "./_auth.js";

const router = express.Router();

// GET /api/transfers?origin=&destination=&date=&minPrice=&maxPrice=&page=&limit=
router.get("/", async (req, res) => {
  try {
    const { origin, destination, date, minPrice, maxPrice } = req.query;
    const page = parseInt(req.query.page || "1", 10);
    const limit = Math.min(parseInt(req.query.limit || "10", 10), 50);
    const offset = (page - 1) * limit;

    const filters = [];
    const params = [];

    if (origin) {
      filters.push("origin = ?");
      params.push(origin);
    }
    if (destination) {
      filters.push("destination = ?");
      params.push(destination);
    }
    if (date) {
      filters.push("date = ?");
      params.push(date);
    }
    if (minPrice) {
      filters.push("price >= ?");
      params.push(Number(minPrice));
    }
    if (maxPrice) {
      filters.push("price <= ?");
      params.push(Number(maxPrice));
    }

    const where = filters.length ? `WHERE ${filters.join(" AND ")}` : "";

    const rows = await query(
      `SELECT * FROM transfers ${where} ORDER BY date ASC, id ASC LIMIT ${limit} OFFSET ${offset}`,
      params,
    );

    res.json({ page, limit, results: rows });
  } catch (err) {
    console.error("Error fetching transfers:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

// POST /api/transfers
router.post("/", requireAuth, requireRole(["agency", "admin"]), requireFields(["service", "origin", "destination", "date", "price"]), numberField("price", { min: 0 }), async (req, res) => {
  try {
    const { service, origin, destination, date, price, created_by } = req.body || {};
    if (!service || !origin || !destination || !date || price == null) {
      return res.status(400).json({ error: "Missing required fields" });
    }
    const amount = Number(price);
    if (!Number.isFinite(amount) || amount < 0) {
      return res.status(400).json({ error: "Invalid price" });
    }
    const result = await query(
      "INSERT INTO transfers (service, origin, destination, date, price, created_by) VALUES (?, ?, ?, ?, ?, ?)",
      [service, origin, destination, date, amount, created_by || null],
    );
    const rows = await query("SELECT * FROM transfers WHERE id = ?", [result.insertId]);
    res.status(201).json(rows[0]);
  } catch (err) {
    console.error("Error creating transfer:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;




