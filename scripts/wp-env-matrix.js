const fs = require("fs");
const path = require("path");
const YAML = require("yaml");

const workflowPath = path.join(
	__dirname,
	"..",
	".github",
	"workflows",
	"test.yml",
);

function parseVersionArray(raw, variableName) {
	const marker = `${variableName}='`;
	const start = raw.indexOf(marker);
	if (start === -1) {
		return null;
	}

	const jsonStart = start + marker.length;
	const jsonEnd = raw.indexOf("]'", jsonStart);
	if (jsonEnd === -1) {
		return null;
	}

	try {
		return JSON.parse(raw.slice(jsonStart, jsonEnd + 1));
	} catch {
		return null;
	}
}

function getMatrixIncludes() {
	if (!fs.existsSync(workflowPath)) {
		throw new Error(`Workflow file not found at ${workflowPath}`);
	}

	const raw = fs.readFileSync(workflowPath, "utf8");
	const doc = YAML.parse(raw);

	const includes =
		doc &&
		doc.jobs &&
		doc.jobs["ci-current"] &&
		doc.jobs["ci-current"].strategy &&
		doc.jobs["ci-current"].strategy.matrix &&
		doc.jobs["ci-current"].strategy.matrix.include;

	if (
		Array.isArray(includes) &&
		includes.length > 0 &&
		typeof includes[0] === "object"
	) {
		return includes;
	}

	const matrixSet =
		process.env.WP_ENV_MATRIX === "full"
			? "FULL_VERSIONS"
			: "REDUCED_VERSIONS";
	const versions = parseVersionArray(raw, matrixSet);

	if (!Array.isArray(versions) || versions.length === 0) {
		throw new Error(
			`Could not find any ${matrixSet} entries in .github/workflows/test.yml`,
		);
	}

	return versions;
}

module.exports = {
	getMatrixIncludes,
};
