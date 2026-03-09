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

	if (!Array.isArray(includes) || includes.length === 0) {
		throw new Error(
			"Could not find any matrix.include entries in .github/workflows/test.yml",
		);
	}

	return includes;
}

module.exports = {
	getMatrixIncludes,
};

