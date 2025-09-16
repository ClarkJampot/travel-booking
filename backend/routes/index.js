import express from "express";
import hotels from "./hotels.js";
import flights from "./flights.js";
import activities from "./activities.js";
import transfers from "./transfers.js";
import bookings from "./bookings.js";
import ads from "./ads.js";

const router = express.Router();

router.use("/hotels", hotels);
router.use("/flights", flights);
router.use("/activities", activities);
router.use("/transfers", transfers);
router.use("/bookings", bookings);
router.use("/ads", ads);

// test endpoint
// router.get("/ping", (req, res) => {
//   res.json({ message: "Test" });
// });

export default router;
