import express from "express";
import { query } from "../models/db.js";

const router = express.Router();

// GET /api/ads?placement=home|listing|sidebar
router.get("/", async (req, res) => {
  try {
    const { placement } = req.query;
    const filters = [];
    const params = [];
    if (placement) {
      filters.push("placement = ?");
      params.push(placement);
    }
    const where = filters.length ? `WHERE ${filters.join(" AND ")}` : "";
    const rows = await query(`SELECT * FROM ads ${where} AND active = 1`.replace("WHERE  AND", "WHERE "), params);
    res.json(rows);
  } catch (err) {
    console.error("Error fetching ads:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;



