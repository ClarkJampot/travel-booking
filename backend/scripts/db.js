// backend/scripts/db.js
import dotenv from "dotenv";
import { execSync } from "child_process";
import path from "path";
import { fileURLToPath } from "url";

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DB_USER = process.env.DB_USER;
const DB_PASS = process.env.DB_PASS;
const DB_NAME = process.env.DB_NAME;
const DB_HOST = process.env.DB_HOST || "localhost";
const DB_PORT = process.env.DB_PORT || "3306";
const MYSQL_BIN = process.env.MYSQL_BIN || "mysql"; // allow overriding mysql path

if (!DB_USER || !DB_PASS || !DB_NAME) {
  console.error("Missing database environment variables in .env");
  process.exit(1);
}

const schemaPath = path.resolve(__dirname, "../../database/schema.sql");
const seedPath = path.resolve(__dirname, "../../database/seed.sql");

function run(command) {
  console.log(`> ${command}`);
  execSync(command, { stdio: "inherit" });
}

function baseCmd() {
  // Quote MYSQL_BIN to support paths with spaces
  return `"${MYSQL_BIN}" -h${DB_HOST} -P${DB_PORT} -u${DB_USER} -p${DB_PASS}`;
}

function init() {
  run(`${baseCmd()} ${DB_NAME} < "${schemaPath}"`);
}

function seed() {
  run(`${baseCmd()} ${DB_NAME} < "${seedPath}"`);
}

function reset() {
  run(
    `${baseCmd()} -e "DROP DATABASE IF EXISTS ${DB_NAME}; CREATE DATABASE ${DB_NAME};"`,
  );
  init();
}

const cmd = process.argv[2];

switch (cmd) {
  case "init":
    init();
    break;
  case "seed":
    seed();
    break;
  case "reset":
    reset();
    break;
  case "reset:seed":
    reset();
    seed();
    break;
  default:
    console.log("Usage: node scripts/db.js [init|seed|reset|reset:seed]");
}
