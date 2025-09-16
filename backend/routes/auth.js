import express from "express";
import { findUserByEmail } from "./_auth.js";

const router = express.Router();

// NOTE: For school demo purposes only - this does NOT verify password
router.post("/login", async (req, res) => {
  try {
    const { email } = req.body || {};
    if (!email) return res.status(400).json({ error: "Missing email" });
    const user = await findUserByEmail(email);
    if (!user) return res.status(401).json({ error: "Invalid credentials" });
    req.session.user = { id: user.id, email: user.email, name: user.full_name, role: user.role };
    res.json({ user: req.session.user });
  } catch (err) {
    console.error("Login error:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

router.post("/logout", (req, res) => {
  if (req.session) {
    req.session.destroy(() => {
      res.json({ ok: true });
    });
  } else {
    res.json({ ok: true });
  }
});

router.get("/me", (req, res) => {
  res.json({ user: req.session?.user || null });
});

export default router;


