import type { ActionFunctionArgs } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";

export const action = async ({ request }: ActionFunctionArgs) => {
    const { payload, session, topic, shop } = await authenticate.webhook(request);
    console.log(`Received ${topic} webhook for ${shop}`);

    const rawCurrent = payload.current;
    if (!Array.isArray(rawCurrent) || !rawCurrent.every((s) => typeof s === "string")) {
        return new Response();
    }
    const current = rawCurrent as string[];
    if (session) {
        await db.session.update({
            where: {
                id: session.id
            },
            data: {
                scope: current.join(","),
            },
        });
    }
    return new Response();
};
